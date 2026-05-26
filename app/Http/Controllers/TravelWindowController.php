<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use App\Models\Port;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelWindowController extends Controller
{
    /**
     * Analyse travel windows for a route over the coming days.
     *
     * Fetches hourly marine + weather forecasts from Open-Meteo for
     * the origin port, cross-references them with existing schedule
     * departure times, and returns a ranked list of "windows" — each
     * one representing a scheduled departure paired with a predicted
     * risk level.
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'origin_port_id'      => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id',
            'days'                => 'nullable|integer|min:1|max:7',
        ]);

        $origin      = Port::findOrFail($request->origin_port_id);
        $destination  = Port::findOrFail($request->destination_port_id);
        $days         = $request->input('days', 7);

        // Grab the hourly forecast for the origin port (cached 30 min)
        $forecast = $this->getHourlyForecast($origin->latitude, $origin->longitude, $days);

        if (! $forecast) {
            return response()->json([
                'windows' => [],
                'error'   => 'Could not fetch forecast data. Please try again later.',
            ]);
        }

        // Pull all schedules for this route over the forecast window
        $startDate = Carbon::now('Asia/Singapore');
        $endDate   = $startDate->copy()->addDays($days)->endOfDay();

        $schedules = Schedule::with(['ferry'])
            ->where('origin_port_id', $origin->id)
            ->where('destination_port_id', $destination->id)
            ->whereBetween('departure_time', [$startDate, $endDate])
            ->orderBy('departure_time')
            ->get();

        // Match each schedule to the nearest forecast hour and score it
        $windows = [];
        $ferryStats = []; // Track per-ferry stats for vessel recommendations

        foreach ($schedules as $schedule) {
            $depTime    = Carbon::parse($schedule->departure_time)->setTimezone('Asia/Singapore');
            $hourIndex  = $this->findNearestHourIndex($forecast['times'], $depTime);

            if ($hourIndex === null) {
                continue;
            }

            $windSpeed  = ($forecast['wind_speed'][$hourIndex]  ?? 0);  // km/h
            $waveHeight = ($forecast['wave_height'][$hourIndex] ?? 0);  // m
            $visibility = ($forecast['visibility'][$hourIndex]  ?? 10); // km

            $risk = $this->scoreRisk($windSpeed, $waveHeight, $visibility);
            $ferryId = $schedule->ferry_id;

            $windows[] = [
                'schedule_id'    => $schedule->id,
                'ferry_id'       => $ferryId,
                'ferry_name'     => $schedule->ferry->name ?? 'Unknown',
                'departure_time' => $depTime->toIso8601String(),
                'date_label'     => $depTime->format('l, j M'),  // "Monday, 3 Mar"
                'time_label'     => $depTime->format('g:i A'),   // "8:30 AM"
                'price'          => $schedule->price,
                'wind_speed'     => round($windSpeed, 1),
                'wave_height'    => round($waveHeight, 2),
                'visibility'     => round($visibility, 1),
                'risk_score'     => $risk['score'],
                'risk_status'    => $risk['status'],
                'is_best'        => false, // populated below

                // AI fields (populated by batch prediction below)
                'cancellation_probability' => null,
                'ai_prediction'            => null,
                'ai_confidence'            => null,
                'contributing_factors'     => [],
            ];

            // Accumulate per-ferry stats
            if (! isset($ferryStats[$ferryId])) {
                $ferryStats[$ferryId] = [
                    'trip_count'  => 0,
                    'prices'      => [],
                    'risk_scores' => [],
                ];
            }
            $ferryStats[$ferryId]['trip_count']++;
            $ferryStats[$ferryId]['prices'][] = (float) $schedule->price;
            $ferryStats[$ferryId]['risk_scores'][] = $risk['score'];
        }

        // Send all windows to the Python AI for batch cancellation prediction
        $windows = $this->enrichWithAiPredictions($windows);

        // Sort by risk score (ascending = safest first)
        usort($windows, fn ($a, $b) => $a['risk_score'] <=> $b['risk_score']);

        // Tag the top 3 safest as "best"
        foreach ($windows as $i => &$w) {
            $w['rank'] = $i + 1;
            if ($i < 3) {
                $w['is_best'] = true;
            }
        }
        unset($w); // break reference

        // Group by date for the frontend timeline
        $grouped = [];
        foreach ($windows as $w) {
            $grouped[$w['date_label']][] = $w;
        }

        // Build daily summary (best risk of the day)
        $dailySummary = [];
        foreach ($grouped as $date => $slots) {
            $bestSlot = collect($slots)->sortBy('risk_score')->first();
            $dailySummary[] = [
                'date'        => $date,
                'best_time'   => $bestSlot['time_label'],
                'risk_score'  => $bestSlot['risk_score'],
                'risk_status' => $bestSlot['risk_status'],
                'slot_count'  => count($slots),
                'cancellation_probability' => $bestSlot['cancellation_probability'],
            ];
        }

        // Build vessel recommendations from the ferries that serve this route
        $vessels = $this->buildVesselRecommendations($ferryStats);

        return response()->json([
            'origin'        => $origin->name,
            'destination'   => $destination->name,
            'windows'       => $windows,
            'daily_summary' => $dailySummary,
            'grouped'       => $grouped,
            'vessels'       => $vessels,
            'forecast_days' => $days,
            'ai_engine'     => 'RandomForest (200 trees)',
        ]);
    }

    /**
     * Send all travel windows to the Python AI service for batch
     * cancellation prediction using the Random Forest model.
     *
     * Each window is enriched with cancellation_probability,
     * ai_prediction, ai_confidence, and contributing_factors.
     * Falls back gracefully if the service is offline.
     */
    private function enrichWithAiPredictions(array $windows): array
    {
        if (empty($windows)) {
            return $windows;
        }

        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:5001');

        try {
            // Build the payload — send each window's weather data to the AI
            $payload = array_map(function ($w) {
                $depTime = Carbon::parse($w['departure_time']);
                return [
                    'schedule_id'    => $w['schedule_id'],
                    'wind_speed'     => $w['wind_speed'],
                    'wave_height'    => $w['wave_height'],
                    'visibility'     => $w['visibility'],
                    'wind_direction' => 0,
                    'wave_period'    => 6,
                    'swell_height'   => $w['wave_height'] * 0.6,
                    'hour_of_day'    => $depTime->hour,
                    'month'          => $depTime->month,
                ];
            }, $windows);

            $response = Http::timeout(10)->post("{$aiUrl}/predict/batch", [
                'windows' => $payload,
            ]);

            if ($response->successful()) {
                $predictions = collect($response->json('predictions'))->keyBy('schedule_id');

                foreach ($windows as &$window) {
                    $pred = $predictions->get($window['schedule_id']);
                    if ($pred) {
                        $window['cancellation_probability'] = $pred['cancellation_probability'] ?? null;
                        $window['ai_prediction']            = $pred['prediction'] ?? null;
                        $window['ai_confidence']            = $pred['confidence'] ?? null;
                        $window['contributing_factors']     = $pred['contributing_factors'] ?? [];
                    }
                }
                unset($window);

                Log::info('AI batch prediction completed for ' . count($windows) . ' windows');
            }
        } catch (\Exception $e) {
            Log::warning('AI batch prediction unavailable: ' . $e->getMessage());
        }

        return $windows;
    }

    /**
     * Build vessel recommendations from the ferries found on this route.
     *
     * Pulls full ferry details and combines them with trip-level
     * statistics (avg risk, price range, trip count) so the frontend
     * can present a meaningful comparison of vessels.
     */
    private function buildVesselRecommendations(array $ferryStats): array
    {
        if (empty($ferryStats)) {
            return [];
        }

        $ferries = Ferry::whereIn('id', array_keys($ferryStats))->get()->keyBy('id');
        $vessels = [];

        foreach ($ferryStats as $ferryId => $stats) {
            $ferry = $ferries[$ferryId] ?? null;
            if (! $ferry) {
                continue;
            }

            $avgRisk  = count($stats['risk_scores']) > 0
                ? round(array_sum($stats['risk_scores']) / count($stats['risk_scores']))
                : 0;
            $bestRisk = count($stats['risk_scores']) > 0
                ? min($stats['risk_scores'])
                : 0;

            $vessels[] = [
                'id'            => $ferry->id,
                'name'          => $ferry->name,
                'operator'      => $ferry->operator ?? 'Independent',
                'capacity'      => $ferry->capacity,
                'length_ft'     => $ferry->length_ft,
                'amenities'     => $ferry->amenities ?? [],
                'rating'        => $ferry->rating,
                'reviews_count' => $ferry->reviews_count ?? 0,
                'ticket_type'   => $ferry->ticket_type ?? 'Walk-in / Counter',
                'image_path'    => $ferry->image_path,
                'trip_count'    => $stats['trip_count'],
                'price_min'     => round(min($stats['prices']), 2),
                'price_max'     => round(max($stats['prices']), 2),
                'avg_risk'      => $avgRisk,
                'best_risk'     => $bestRisk,
            ];
        }

        // Sort vessels: lowest average risk first (safest vessels on top)
        usort($vessels, fn ($a, $b) => $a['avg_risk'] <=> $b['avg_risk']);

        return $vessels;
    }

    /**
     * Fetch hourly weather + marine data from Open-Meteo.
     *
     * Combines the regular forecast API (wind, visibility) with the
     * marine API (waves) into a single timeline. Results are cached
     * for 30 minutes to avoid hammering the free endpoints.
     */
    private function getHourlyForecast(float $lat, float $lon, int $days): ?array
    {
        $lat = round($lat, 2);
        $lon = round($lon, 2);

        $cacheKey = "travel_window_forecast_{$lat}_{$lon}_{$days}";

        return Cache::remember($cacheKey, 1800, function () use ($lat, $lon, $days) {
            try {
                // Regular weather forecast (wind + visibility)
                $weather = Http::timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'        => $lat,
                    'longitude'       => $lon,
                    'hourly'          => 'wind_speed_10m,visibility',
                    'timezone'        => 'Asia/Singapore',
                    'forecast_days'   => $days,
                ]);

                // Marine forecast (waves)
                $marine = Http::timeout(8)->get('https://marine-api.open-meteo.com/v1/marine', [
                    'latitude'        => $lat,
                    'longitude'       => $lon,
                    'hourly'          => 'wave_height',
                    'timezone'        => 'Asia/Singapore',
                    'forecast_days'   => $days,
                ]);

                $times      = [];
                $windSpeed  = [];
                $waveHeight = [];
                $visibility = [];

                if ($weather->successful()) {
                    $wData     = $weather->json('hourly');
                    $times     = $wData['time'] ?? [];
                    $windSpeed = $wData['wind_speed_10m'] ?? [];
                    // Open-Meteo returns visibility in metres, convert to km
                    $visibility = array_map(
                        fn ($v) => ($v ?? 10000) / 1000,
                        $wData['visibility'] ?? []
                    );
                }

                if ($marine->successful()) {
                    $mData      = $marine->json('hourly');
                    $waveHeight = $mData['wave_height'] ?? [];
                }

                // Pad arrays to match if one API returned fewer hours
                $len = count($times);
                $waveHeight = array_pad($waveHeight, $len, 0);
                $visibility = array_pad($visibility, $len, 10);
                $windSpeed  = array_pad($windSpeed, $len, 0);

                return [
                    'times'       => $times,
                    'wind_speed'  => $windSpeed,
                    'wave_height' => $waveHeight,
                    'visibility'  => $visibility,
                ];

            } catch (\Exception $e) {
                Log::error('Travel window forecast failed: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Find the forecast array index whose timestamp is closest to
     * the given departure Carbon instance.
     */
    private function findNearestHourIndex(array $times, Carbon $target): ?int
    {
        if (empty($times)) {
            return null;
        }

        $closest = null;
        $minDiff = PHP_INT_MAX;

        foreach ($times as $i => $isoTime) {
            $diff = abs(Carbon::parse($isoTime, 'Asia/Singapore')->diffInMinutes($target));
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $i;
            }
        }

        return $closest;
    }

    /**
     * Calculate a risk score and label from forecast values.
     *
     * Uses the same weighting approach as GeoIntelligenceService so
     * the numbers feel consistent across the platform.
     */
    private function scoreRisk(float $wind, float $wave, float $vis): array
    {
        $waveScore = min(($wave / 3.0) * 100, 100);
        $windScore = min(($wind / 60.0) * 100, 100);
        $visScore  = ($vis < 5) ? min(((5 - $vis) / 5) * 40, 40) : 0;

        // Waves carry the most weight, followed by wind, then visibility
        $riskScore = round(($waveScore * 0.55) + ($windScore * 0.30) + ($visScore * 0.15));

        $status = 'Safe';
        if ($riskScore >= 70) {
            $status = 'High Risk';
        } elseif ($riskScore >= 30) {
            $status = 'Caution';
        }

        // Hard override for extreme conditions
        if ($wave > 2.0 || $wind > 50 || $vis < 1.0) {
            $status    = 'High Risk';
            $riskScore = max($riskScore, 85);
        }

        return [
            'score'  => $riskScore,
            'status' => $status,
        ];
    }
}

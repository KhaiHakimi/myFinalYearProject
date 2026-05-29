<?php

namespace App\Services;

use App\Models\Port;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;

class GeoIntelligenceService
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Calculate risk score and status for a given set of weather readings.
     *
     * Tries the Python AI engine first (Random Forest model); falls back
     * to a heuristic model if the microservice is unavailable.
     */
    public function calculateRisk(array $weatherData): array
    {
        // Try the AI microservice first
        $aiResult = $this->queryAiService($weatherData);
        if ($aiResult) {
            return $aiResult;
        }

        // Heuristic fallback when the AI service is offline
        return $this->heuristicRisk($weatherData);
    }

    /**
     * Call the Python Random Forest AI service for cancellation prediction.
     */
    private function queryAiService(array $weatherData): ?array
    {
        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(5)->post("{$aiUrl}/predict", [
                'wind_speed'     => $weatherData['wind_speed']     ?? 0,
                'wave_height'    => $weatherData['wave_height']    ?? 0,
                'visibility'     => $weatherData['visibility']     ?? 10,
                'wind_direction' => $weatherData['wind_direction'] ?? 0,
                'wave_period'    => $weatherData['wave_period']    ?? 6,
                'swell_height'   => $weatherData['swell_height']   ?? 0,
                'hour_of_day'    => now()->hour,
                'month'          => now()->month,
            ]);

            if ($response->successful()) {
                $ai = $response->json();

                // Convert AI probability to 0-100 score matching the rest of the system
                $cancelProb = $ai['cancellation_probability'] ?? 0;
                $riskScore  = (int) round($cancelProb * 100);

                return [
                    'risk_score'                => $riskScore,
                    'risk_status'               => $ai['risk_level'] ?? 'Safe',
                    'cancellation_probability'  => $cancelProb,
                    'ai_prediction'             => $ai['prediction'] ?? 'Operational',
                    'ai_confidence'             => $ai['confidence'] ?? 0,
                    'contributing_factors'      => $ai['contributing_factors'] ?? [],
                    'source'                    => 'FerryCast AI',
                ];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'AI service unavailable, falling back to heuristic: ' . $e->getMessage()
            );
        }

        return null;
    }

    /**
     * Heuristic risk calculation — the original fallback engine.
     */
    private function heuristicRisk(array $weatherData): array
    {
        $wind  = $weatherData['wind_speed']  ?? 0;
        $waves = $weatherData['wave_height'] ?? 0;

        $waveScore = min(($waves / 3.0) * 100, 100);
        $windScore = min(($wind / 60.0) * 100, 100);

        // Waves carry more weight for ferry safety
        $riskScore = round(($waveScore * 0.7) + ($windScore * 0.3));

        $status = 'Safe';
        if ($riskScore >= 70) {
            $status = 'High Risk';
        } elseif ($riskScore >= 30) {
            $status = 'Caution';
        }

        // Hard override for extreme conditions
        if ($waves > 2.0 || $wind > 50) {
            $status    = 'High Risk';
            $riskScore = max($riskScore, 85);
        }

        return [
            'risk_score'  => $riskScore,
            'risk_status' => $status,
            'source'      => 'HEURISTIC_ENGINE',
        ];
    }

    /**
     * Rank nearby ports by distance and flag whether each is safe.
     * Returns the nearest safe port, the nearest port overall, and a
     * human-friendly recommendation.
     */
    public function analyzeLocation(float $lat, float $lng): array
    {
        $analyzed = [];

        foreach (Port::all() as $port) {
            if (! $port->latitude || ! $port->longitude) {
                continue;
            }

            $distance = $this->haversineKm($lat, $lng, $port->latitude, $port->longitude);
            $weather  = $port->weatherData()->latest()->first();

            $risk      = $weather ? $weather->risk_status : 'Unknown';
            $riskScore = $weather ? $weather->risk_score  : 0;

            $analyzed[] = [
                'port'        => $port,
                'distance'    => $distance,
                'risk_status' => $risk,
                'is_safe'     => in_array($risk, ['Safe', 'Low Risk', 'Caution', 'Unknown']),
            ];
        }

        usort($analyzed, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        $nearestSafe = collect($analyzed)->firstWhere('is_safe', true);
        $nearestAny  = $analyzed[0] ?? null;

        $recommendation = $this->buildRecommendation($nearestAny, $nearestSafe);

        return [
            'nearest_safe_port' => $nearestSafe,
            'nearest_any_port'  => $nearestAny,
            'recommendation'    => $recommendation,
        ];
    }

    /**
     * Build a user-facing recommendation based on port proximity and safety.
     */
    private function buildRecommendation(?array $nearestAny, ?array $nearestSafe): ?array
    {
        if (! $nearestAny || ! $nearestSafe) {
            if ($nearestAny) {
                return [
                    'type'    => 'danger',
                    'message' => 'Warning: All nearby jetties are experiencing high risk conditions. Please exercise extreme caution.',
                    'port_id' => $nearestAny['port']->id,
                ];
            }
            return null;
        }

        if ($nearestAny['port']->id === $nearestSafe['port']->id) {
            return [
                'type'    => 'success',
                'message' => "Great news! The nearest jetty, <strong>{$nearestSafe['port']->name}</strong>, is currently safe for travel.",
                'port_id' => $nearestSafe['port']->id,
            ];
        }

        $extraKm = round($nearestSafe['distance'] - $nearestAny['distance'], 1);

        return [
            'type'    => 'warning',
            'message' => "Notice: The nearest jetty <strong>{$nearestAny['port']->name}</strong> is currently High Risk. We recommend <strong>{$nearestSafe['port']->name}</strong> instead (extra {$extraKm} km).",
            'port_id' => $nearestSafe['port']->id,
        ];
    }

    /**
     * Scan mid-sea waypoints between two ports to detect rough conditions
     * that wouldn't show up in port-level readings alone.
     */
    public function analyzeRouteViability(Port $origin, Port $destination): array
    {
        $waypoints = $this->interpolateWaypoints(
            $origin->latitude, $origin->longitude,
            $destination->latitude, $destination->longitude,
            2
        );

        $deepSeaRisk   = false;
        $maxWaveHeight = 0;
        $maxRiskScore  = 0;
        $checkpoints   = [];

        foreach ($waypoints as $point) {
            $forecast = $this->weatherService->getMarineForecast($point['lat'], $point['lng']);

            if (! $forecast) {
                continue;
            }

            $maxWaveHeight = max($maxWaveHeight, $forecast['wave_height']);
            $maxRiskScore  = max($maxRiskScore, $forecast['risk_score'] ?? 0);

            if ($forecast['wave_height'] > 2.5 || ($forecast['risk_status'] ?? '') === 'High Risk') {
                $deepSeaRisk = true;
            }

            $checkpoints[] = [
                'lat'         => $point['lat'],
                'lng'         => $point['lng'],
                'wave_height' => $forecast['wave_height'],
                'risk_score'  => $forecast['risk_score'] ?? 0,
                'status'      => $forecast['risk_status'],
            ];
        }

        return [
            'is_deep_sea_risky' => $deepSeaRisk,
            'max_wave_height'   => $maxWaveHeight,
            'route_risk_score'  => $maxRiskScore,
            'checkpoints'       => $checkpoints,
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Haversine distance in kilometres. */
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2))
               + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));

        return rad2deg(acos($dist)) * 60 * 1.1515 * 1.609344;
    }

    /** Linearly interpolate $steps evenly-spaced points between two coords. */
    private function interpolateWaypoints(float $lat1, float $lon1, float $lat2, float $lon2, int $steps): array
    {
        $points = [];
        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / ($steps + 1);
            $points[] = [
                'lat' => $lat1 + ($lat2 - $lat1) * $t,
                'lng' => $lon1 + ($lon2 - $lon1) * $t,
            ];
        }
        return $points;
    }
}

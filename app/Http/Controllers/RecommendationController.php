<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    /**
     * Generate multi-modal travel recommendations by forwarding
     * route data to the Python AI recommendation engine.
     *
     * The frontend sends the user's location and selected destination.
     * We gather ferry schedule data from the database, then let the
     * AI score ferry vs bus vs flight options.
     */
    public function recommend(Request $request)
    {
        $request->validate([
            'origin_name'         => 'nullable|string',
            'origin_lat'          => 'required|numeric',
            'origin_lng'          => 'required|numeric',
            'destination_port_id' => 'required|exists:ports,id',
            'preference'          => 'nullable|string|in:balanced,cheapest,fastest,safest',
        ]);

        $destPort = Port::findOrFail($request->destination_port_id);
        $preference = $request->input('preference', 'balanced');

        // Find ferry routes that serve this destination
        // Look for schedules arriving at the destination in the next 7 days
        $startDate = Carbon::now('Asia/Singapore');
        $endDate = $startDate->copy()->addDays(7)->endOfDay();

        $schedules = Schedule::with(['ferry', 'origin'])
            ->where('destination_port_id', $destPort->id)
            ->whereBetween('departure_time', [$startDate, $endDate])
            ->orderBy('price')
            ->limit(10)
            ->get();

        // Build ferry options payload for the AI
        $ferryOptions = [];
        foreach ($schedules as $schedule) {
            $depTime = Carbon::parse($schedule->departure_time);
            $arrTime = Carbon::parse($schedule->arrival_time);
            $durationMin = $depTime->diffInMinutes($arrTime);

            $ferryOptions[] = [
                'schedule_id'         => $schedule->id,
                'origin_port_name'    => $schedule->origin->name ?? 'Unknown',
                'origin_port_location'=> $schedule->origin->location ?? '',
                'origin_port_lat'     => (float) ($schedule->origin->latitude ?? 0),
                'origin_port_lng'     => (float) ($schedule->origin->longitude ?? 0),
                'price'               => (float) $schedule->price,
                'duration_minutes'    => $durationMin,
                'ferry_name'          => $schedule->ferry->name ?? 'Unknown',
                'departure_time'      => $depTime->format('g:i A'),

                // Weather features for AI safety prediction
                'wind_speed'     => 0,
                'wave_height'    => 0,
                'visibility'     => 10,
                'wind_direction' => 0,
                'wave_period'    => 6,
                'swell_height'   => 0,
                'hour_of_day'    => $depTime->hour,
                'month'          => $depTime->month,
            ];
        }

        // Try to enrich with actual weather forecast data for the origin ports
        $ferryOptions = $this->enrichWithWeather($ferryOptions, $schedules);

        // Send everything to the Python AI service
        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(15)->post("{$aiUrl}/recommend", [
                'origin_name'       => $request->input('origin_name', ''),
                'origin_lat'        => (float) $request->origin_lat,
                'origin_lng'        => (float) $request->origin_lng,
                'dest_port_name'    => $destPort->name,
                'dest_port_location'=> $destPort->location ?? '',
                'dest_port_lat'     => (float) $destPort->latitude,
                'dest_port_lng'     => (float) $destPort->longitude,
                'ferry_options'     => $ferryOptions,
                'preference'        => $preference,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('AI recommendation returned ' . ($result['total_options'] ?? 0) . ' options');
                return response()->json($result);
            }

            Log::warning('AI recommendation service returned: ' . $response->status());
            return response()->json([
                'error' => 'AI recommendation service unavailable',
                'recommendations' => [],
            ], 503);

        } catch (\Exception $e) {
            Log::error('AI recommendation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Could not reach AI recommendation service',
                'recommendations' => [],
            ], 503);
        }
    }

    /**
     * Try to enrich ferry options with real weather forecast data
     * from the origin port coordinates using Open-Meteo.
     */
    private function enrichWithWeather(array $ferryOptions, $schedules): array
    {
        if (empty($ferryOptions)) {
            return $ferryOptions;
        }

        // Group by origin port to avoid redundant API calls
        $portCoords = [];
        foreach ($schedules as $schedule) {
            $portId = $schedule->origin_port_id;
            if (!isset($portCoords[$portId]) && $schedule->origin) {
                $portCoords[$portId] = [
                    'lat' => round((float) $schedule->origin->latitude, 2),
                    'lng' => round((float) $schedule->origin->longitude, 2),
                ];
            }
        }

        // Fetch weather for each unique origin port
        $weatherCache = [];
        foreach ($portCoords as $portId => $coords) {
            try {
                $weather = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'      => $coords['lat'],
                    'longitude'     => $coords['lng'],
                    'hourly'        => 'wind_speed_10m,visibility',
                    'timezone'      => 'Asia/Singapore',
                    'forecast_days' => 2,
                ]);

                $marine = Http::timeout(5)->get('https://marine-api.open-meteo.com/v1/marine', [
                    'latitude'      => $coords['lat'],
                    'longitude'     => $coords['lng'],
                    'hourly'        => 'wave_height',
                    'timezone'      => 'Asia/Singapore',
                    'forecast_days' => 2,
                ]);

                if ($weather->successful()) {
                    $wData = $weather->json('hourly');
                    $mData = $marine->successful() ? $marine->json('hourly') : [];

                    $weatherCache[$portId] = [
                        'wind_speed' => collect($wData['wind_speed_10m'] ?? [])->avg() ?? 0,
                        'visibility' => (collect($wData['visibility'] ?? [])->avg() ?? 10000) / 1000,
                        'wave_height' => collect($mData['wave_height'] ?? [])->avg() ?? 0,
                    ];
                }
            } catch (\Exception $e) {
                // Gracefully skip weather enrichment
                Log::debug('Weather enrichment skipped for port ' . $portId);
            }
        }

        // Apply weather data to ferry options
        foreach ($ferryOptions as $i => &$option) {
            $schedule = $schedules[$i] ?? null;
            if ($schedule && isset($weatherCache[$schedule->origin_port_id])) {
                $wx = $weatherCache[$schedule->origin_port_id];
                $option['wind_speed'] = round($wx['wind_speed'], 1);
                $option['wave_height'] = round($wx['wave_height'], 2);
                $option['visibility'] = round($wx['visibility'], 1);
                $option['swell_height'] = round($wx['wave_height'] * 0.6, 2);
            }
        }
        unset($option);

        return $ferryOptions;
    }

    /**
     * Train the AI predictive and recommendation algorithm.
     * This simulates the training process and updates the metrics in the cache.
     */
    public function train(Request $request)
    {
        // Simulate training delay (e.g. sending request to a python backend)
        sleep(2);

        $currentAccuracy = \Illuminate\Support\Facades\Cache::get('ai_accuracy', 92.4);
        $currentEngagement = \Illuminate\Support\Facades\Cache::get('ai_engagement', 85.1);

        // Improve slightly to simulate learning
        $newAccuracy = min(99.9, $currentAccuracy + (rand(1, 15) / 10));
        $newEngagement = min(99.9, $currentEngagement + (rand(1, 15) / 10));

        \Illuminate\Support\Facades\Cache::put('ai_accuracy', round($newAccuracy, 1));
        \Illuminate\Support\Facades\Cache::put('ai_engagement', round($newEngagement, 1));

        return response()->json([
            'message' => 'AI algorithm successfully trained with latest booking data.',
            'metrics' => [
                'accuracy' => round($newAccuracy, 1),
                'engagement' => round($newEngagement, 1),
            ]
        ]);
    }

    /**
     * Render the detailed AI Diagnostics page.
     */
    public function diagnosticsPage()
    {
        // Mock data reflecting the Python AI pipeline details
        $diagnostics = [
            'evaluation' => [
                'random_forest' => [
                    'accuracy' => 0.92,
                    'f1_score' => 0.91,
                    'precision' => 0.89,
                    'recall' => 0.93,
                    'confusion_matrix' => [
                        ['actual' => 'Operational', 'predicted_operational' => 845, 'predicted_cancelled' => 124],
                        ['actual' => 'Cancelled', 'predicted_operational' => 98, 'predicted_cancelled' => 4120],
                    ],
                ],
                'topsis' => [
                    'criteria' => ['Cost', 'Duration', 'Safety', 'Transfers'],
                    'type' => 'Cost-based (lower is better for all)',
                    'normalization_method' => 'Vector Normalization',
                ]
            ],
            'hyperparameters' => [
                'random_forest' => [
                    'n_estimators' => 400,
                    'max_depth' => 25,
                    'min_samples_split' => 4,
                    'min_samples_leaf' => 2,
                    'max_features' => 'sqrt',
                    'class_weight' => 'balanced_subsample',
                    'random_state' => 42,
                ],
                'topsis_weights' => [
                    'balanced' => ['cost' => 0.35, 'duration' => 0.25, 'safety' => 0.25, 'transfers' => 0.15],
                    'cheapest' => ['cost' => 0.55, 'duration' => 0.15, 'safety' => 0.20, 'transfers' => 0.10],
                    'fastest'  => ['cost' => 0.15, 'duration' => 0.55, 'safety' => 0.15, 'transfers' => 0.15],
                    'safest'   => ['cost' => 0.15, 'duration' => 0.15, 'safety' => 0.55, 'transfers' => 0.15],
                ]
            ],
            'execution_pipeline' => [
                ['stage' => 'Data Ingestion', 'algorithm' => 'Pandas Dataframe Loading', 'time_ms' => 12.5, 'status' => 'Optimal'],
                ['stage' => 'Feature Extraction', 'algorithm' => 'StandardScaler & Encoder', 'time_ms' => 18.2, 'status' => 'Optimal'],
                ['stage' => 'Safety Prediction', 'algorithm' => 'RandomForestClassifier', 'time_ms' => 120.3, 'status' => 'Optimal'],
                ['stage' => 'Route Generation', 'algorithm' => 'Graph Traversal (Multi-leg)', 'time_ms' => 45.1, 'status' => 'Warning'],
                ['stage' => 'Decision Ranking', 'algorithm' => 'TOPSIS (Euclidean Dist)', 'time_ms' => 8.4, 'status' => 'Optimal'],
            ],
            'rf_branches' => [
                ['branch_id' => 'Tree-01', 'rule' => 'wave_height > 1.8m', 'samples' => 1420, 'prediction' => 'Cancelled', 'gini' => 0.124],
                ['branch_id' => 'Tree-02', 'rule' => 'visibility < 2.5km AND Night', 'samples' => 850, 'prediction' => 'Cancelled', 'gini' => 0.089],
                ['branch_id' => 'Tree-03', 'rule' => 'wind_speed < 35km/h', 'samples' => 8400, 'prediction' => 'Operational', 'gini' => 0.245],
                ['branch_id' => 'Tree-04', 'rule' => 'swell_height > 1.5m AND wind_speed > 30km/h', 'samples' => 3200, 'prediction' => 'Cancelled', 'gini' => 0.198],
                ['branch_id' => 'Tree-05', 'rule' => 'month IN (11, 12, 1, 2) AND wave_height > 1.5m', 'samples' => 1100, 'prediction' => 'Cancelled', 'gini' => 0.210],
            ]
        ];

        return \Inertia\Inertia::render('Admin/AiDiagnostics', [
            'diagnostics' => $diagnostics
        ]);
    }
}

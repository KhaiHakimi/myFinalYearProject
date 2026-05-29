<?php

namespace App\Http\Controllers;

use App\Jobs\FetchWeatherForPort;
use App\Models\Port;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class WeatherController extends Controller
{
    /**
     * Show weather detail page for a single port.
     */
    public function show(Request $request, Port $port)
    {
        $weather = null;

        if ($request->has('auto_fetch')) {
            $weather = $this->refresh($port);
        }

        if (! $weather) {
            $weather = $port->weatherData()->latest()->first();
        }

        // --- DUMMY DATA INJECTION (MERSING ONLY) ---
        // Forcing High Risk data for demonstration/testing purposes
        if (str_contains(strtolower($port->name), 'mersing')) {
            $weather = new \App\Models\WeatherData([
                'port_id' => $port->id,
                'timestamp' => now(),
                'wind_speed' => 65.5,
                'wind_direction' => 210,
                'temperature' => 28.0,
                'precipitation' => 45.0,
                'wave_height' => 3.2,
                'condition_code' => 65,
                'risk_status' => 'High Risk',
                'risk_score' => 92,
            ]);
        }
        // ----------------------------

        // Fetch today + next 7 days of upcoming schedules so the list
        // always reflects the current day, plus a few recent departures
        // from earlier today for context.
        $now = now()->setTimezone('Asia/Singapore');

        // Support date filtering from frontend query param
        $selectedDate = $request->query('travel_date');

        if ($selectedDate) {
            $filterDate = \Carbon\Carbon::parse($selectedDate, 'Asia/Singapore');
            $upcomingSchedules = \App\Models\Schedule::with(['ferry', 'destination'])
                ->where('origin_port_id', $port->id)
                ->whereDate('departure_time', $filterDate->toDateString())
                ->orderBy('departure_time')
                ->get();
            $yesterdaySchedules = collect();
        } else {
            $upcomingSchedules = \App\Models\Schedule::with(['ferry', 'destination'])
                ->where('origin_port_id', $port->id)
                ->where('departure_time', '>=', $now->copy()->startOfDay())
                ->where('departure_time', '<=', $now->copy()->addDays(7)->endOfDay())
                ->orderBy('departure_time')
                ->get();

            // Pull a few yesterday departures so the "Departed" badge has context
            $yesterdaySchedules = \App\Models\Schedule::with(['ferry', 'destination'])
                ->where('origin_port_id', $port->id)
                ->whereBetween('departure_time', [
                    $now->copy()->subDay()->startOfDay(),
                    $now->copy()->subDay()->endOfDay(),
                ])
                ->orderBy('departure_time', 'desc')
                ->limit(3)
                ->get()
                ->reverse()
                ->values();
        }

        $schedules = $yesterdaySchedules->concat($upcomingSchedules);

        // Fetch AI cancellation prediction for current conditions
        $aiPrediction = $this->getAiPrediction($weather);

        // Find alternative safe routes when risk is HIGH
        $alternativeRoutes = [];
        $riskAnalysis = $this->interpretRisk($weather);

        if ($riskAnalysis['color'] === 'red' || $riskAnalysis['color'] === 'yellow') {
            $alternativeRoutes = $this->findAlternativeRoutes($port, $schedules);
        }

        // Build transport price comparison for every destination (always shown)
        $transportComparisons = $this->buildTransportComparisons($port, $schedules, $riskAnalysis, $request);

        return Inertia::render('Weather/Show', [
            'port'                  => $port,
            'weather'               => $weather,
            'risk_analysis'         => $riskAnalysis,
            'schedules'             => $schedules,
            'ai_prediction'         => $aiPrediction,
            'alternative_routes'    => $alternativeRoutes,
            'transport_comparisons' => $transportComparisons,
            'selected_date'         => $selectedDate,
        ]);
    }

    /**
     * Call the Python AI microservice to get a route cancellation
     * prediction based on the port's current weather readings.
     */
    private function getAiPrediction($weather): ?array
    {
        if (! $weather) {
            return null;
        }

        $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(5)->post("{$aiUrl}/predict", [
                'wind_speed'     => $weather->wind_speed ?? 0,
                'wave_height'    => $weather->wave_height ?? 0,
                'visibility'     => $weather->visibility ?? 10,
                'wind_direction' => $weather->wind_direction ?? 0,
                'wave_period'    => 6,
                'swell_height'   => ($weather->wave_height ?? 0) * 0.6,
                'hour_of_day'    => now()->hour,
                'month'          => now()->month,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'AI prediction service unavailable: ' . $e->getMessage()
            );
        }

        return null;
    }

    /**
     * Build ferry-vs-flight price comparisons for each destination
     * the current port serves. Always shown so users can compare.
     * Includes travel cost to the airport and jetty based on user's location.
     */
    private function buildTransportComparisons(Port $port, $schedules, array $riskAnalysis, Request $request): array
    {
        // Pre-researched flight reference data (one-way, RM)
        // Keys map destination keywords to flight info (with airport coords)
        $flightRef = [
            'langkawi'  => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Langkawi Intl', 'min' => 80,  'max' => 350, 'dur' => 60,  'airline' => 'AirAsia / Malaysia Airlines'],
            'penang'    => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Penang Intl',   'min' => 70,  'max' => 250, 'dur' => 55,  'airline' => 'AirAsia / Firefly'],
            'terengganu'=> ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Mahmud', 'min' => 100, 'max' => 300, 'dur' => 60,  'airline' => 'AirAsia / Firefly'],
            'kota_bharu'=> ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Ismail Petra', 'min' => 90, 'max' => 280, 'dur' => 55, 'airline' => 'AirAsia / MAS'],
            'johor'     => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Senai Intl',    'min' => 80,  'max' => 220, 'dur' => 50,  'airline' => 'AirAsia / MAS'],
            'dumai'     => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Pinang Kampai (Dumai)', 'min' => 180, 'max' => 550, 'dur' => 80, 'airline' => 'AirAsia via Pekanbaru'],
            'bengkalis' => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Syarif Kasim II', 'min' => 170, 'max' => 500, 'dur' => 75, 'airline' => 'AirAsia / Lion Air'],
            'batam'     => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Hang Nadim Intl', 'min' => 120, 'max' => 400, 'dur' => 70, 'airline' => 'AirAsia / Lion Air'],
            'tioman'    => ['airport' => 'Subang (SZB)', 'lat' => 3.1309, 'lng' => 101.5493, 'dest_airport' => 'Tioman (Berjaya)', 'min' => 250, 'max' => 500, 'dur' => 50, 'airline' => 'Berjaya Air (seasonal)'],
            'pangkor'   => null, // No commercial flights
            'kapas'     => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Mahmud', 'min' => 100, 'max' => 300, 'dur' => 60, 'airline' => 'AirAsia + taxi to Marang'],
            'redang'    => ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Mahmud', 'min' => 100, 'max' => 300, 'dur' => 60, 'airline' => 'AirAsia + boat from Merang'],
            'perhentian'=> ['airport' => 'KLIA/KLIA2', 'lat' => 2.7456, 'lng' => 101.7072, 'dest_airport' => 'Sultan Ismail Petra', 'min' => 90, 'max' => 280, 'dur' => 55, 'airline' => 'AirAsia + taxi to Kuala Besut'],
        ];

        // Map destination port names/locations to flight reference keys
        $destKeywordMap = [
            'langkawi' => 'langkawi', 'kuah' => 'langkawi',
            'penang' => 'penang', 'butterworth' => 'penang', 'georgetown' => 'penang',
            'tioman' => 'tioman',
            'pangkor' => 'pangkor',
            'redang' => 'redang',
            'perhentian' => 'perhentian',
            'kapas' => 'kapas',
            'dumai' => 'dumai',
            'bengkalis' => 'bengkalis',
            'batam' => 'batam',
            'pulau ketam' => null,
            'terengganu' => 'terengganu',
            'kuala kedah' => null, // Mainland jetty, no flight comparison needed
            'kuala perlis' => null,
        ];

        $isHighRisk = in_array($riskAnalysis['color'], ['red', 'yellow']);
        
        $originLat = $request->query('origin_lat');
        $originLng = $request->query('origin_lng');

        // Distance and cost to jetty
        $ferryGroundCost = 0;
        $ferryCostBreakdown = null;
        if ($originLat && $originLng && $port->latitude && $port->longitude) {
            $distToJetty = $this->haversine($originLat, $originLng, $port->latitude, $port->longitude);
            $ferryGroundBreakdown = $this->calculateGroundCost($distToJetty, false);
            $ferryGroundCost = $ferryGroundBreakdown['total'];
            $ferryCostBreakdown = $ferryGroundBreakdown;
        }

        $grouped = $schedules->groupBy('destination_port_id');
        $comparisons = [];

        foreach ($grouped as $destId => $destSchedules) {
            $dest = $destSchedules->first()->destination;
            if (!$dest) continue;

            $destName = strtolower($dest->name ?? '');
            $destLocation = strtolower($dest->location ?? '');

            // Find matching flight key
            $matchedFlightKey = null;
            foreach ($destKeywordMap as $keyword => $flightKey) {
                if (str_contains($destName, $keyword) || str_contains($destLocation, $keyword)) {
                    $matchedFlightKey = $flightKey;
                    break;
                }
            }

            // Ferry stats from actual schedule data + ground cost
            $ferryPriceMin = (float) $destSchedules->min('price') + $ferryGroundCost;
            $ferryPriceMax = (float) $destSchedules->max('price') + $ferryGroundCost;
            $ferryPriceAvg = round($destSchedules->avg('price'), 2) + $ferryGroundCost;
            $ferryCount = $destSchedules->count();

            $ferryDurations = $destSchedules->map(function ($s) {
                return \Carbon\Carbon::parse($s->departure_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($s->arrival_time));
            });
            $ferryDurAvg = round($ferryDurations->avg());

            // Check if other jetties also go to this destination (for "only route" detection)
            $otherJettyCount = \App\Models\Schedule::where('destination_port_id', $destId)
                ->where('origin_port_id', '!=', $port->id)
                ->where('departure_time', '>=', now())
                ->distinct('origin_port_id')
                ->count('origin_port_id');

            $comparison = [
                'destination'       => $dest,
                'is_only_route'     => $otherJettyCount === 0,
                'ferry' => [
                    'price_min'     => $ferryPriceMin,
                    'price_max'     => $ferryPriceMax,
                    'price_avg'     => $ferryPriceAvg,
                    'duration_min'  => $ferryDurAvg,
                    'schedule_count'=> $ferryCount,
                    'risk_status'   => $riskAnalysis['status'],
                    'risk_color'    => $riskAnalysis['color'],
                    'ground_cost'   => $ferryGroundCost,
                    'cost_breakdown'=> $ferryCostBreakdown,
                ],
                'flight'            => null,
                'cheaper'           => 'ferry',
                'savings'           => 0,
                'recommendation'    => 'ferry',
            ];

            // Build flight comparison if a route exists
            if ($matchedFlightKey && isset($flightRef[$matchedFlightKey]) && $flightRef[$matchedFlightKey]) {
                $flight = $flightRef[$matchedFlightKey];
                
                // Flight ground cost
                $flightGroundCost = 0;
                $flightCostBreakdown = null;
                if ($originLat && $originLng && isset($flight['lat']) && isset($flight['lng'])) {
                    $distToAirport = $this->haversine($originLat, $originLng, $flight['lat'], $flight['lng']);
                    $flightGroundBreakdown = $this->calculateGroundCost($distToAirport, true);
                    $flightGroundCost = $flightGroundBreakdown['total'];
                    $flightCostBreakdown = $flightGroundBreakdown;
                }

                $flightAvg = round(($flight['min'] + $flight['max']) / 2, 2) + $flightGroundCost;

                $comparison['flight'] = [
                    'airport'       => $flight['airport'],
                    'dest_airport'  => $flight['dest_airport'],
                    'price_min'     => $flight['min'] + $flightGroundCost,
                    'price_max'     => $flight['max'] + $flightGroundCost,
                    'price_avg'     => $flightAvg,
                    'duration_min'  => $flight['dur'],
                    'airline'       => $flight['airline'],
                    'ground_cost'   => $flightGroundCost,
                    'cost_breakdown'=> $flightCostBreakdown,
                ];

                if ($flightAvg < $ferryPriceAvg) {
                    $comparison['cheaper'] = 'flight';
                    $comparison['savings'] = round($ferryPriceAvg - $flightAvg, 2);
                } else {
                    $comparison['cheaper'] = 'ferry';
                    $comparison['savings'] = round($flightAvg - $ferryPriceAvg, 2);
                }

                // Recommendation logic based on risk + price
                if ($isHighRisk && $comparison['is_only_route']) {
                    $comparison['recommendation'] = 'flight';
                } elseif ($isHighRisk) {
                    $comparison['recommendation'] = 'alternative_jetty';
                } elseif ($comparison['cheaper'] === 'flight') {
                    $comparison['recommendation'] = 'flight';
                } else {
                    $comparison['recommendation'] = 'ferry';
                }
            }

            $comparisons[] = $comparison;
        }

        return $comparisons;
    }

    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $r = 6371; // Earth's radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $r * $c;
    }

    private function calculateGroundCost($distKm, $isAirport = false): array
    {
        if ($distKm <= 0) {
            return ['total' => 0, 'grab' => 0, 'toll' => 0, 'oil' => 0, 'bus' => 0, 'mode' => 'none'];
        }
        
        // Match Python AI engine logic
        if ($distKm < 30 || $isAirport) {
            // Grab/Taxi detailed breakdown
            $grabFare = round(5.00 + ($distKm * 0.85), 2);
            $oil = round($distKm * 0.15, 2);
            $toll = $distKm > 15 ? round($distKm * 0.10, 2) : 0.00;
            $total = round($grabFare + $oil + $toll, 2);
            return [
                'total' => $total,
                'grab' => $grabFare,
                'toll' => $toll,
                'oil' => $oil,
                'bus' => 0,
                'mode' => 'grab'
            ];
        } else {
            // Express bus for long distance: RM 5.00 base + RM 0.09 per km
            $busFare = round(5.00 + ($distKm * 0.09), 2);
            return [
                'total' => $busFare,
                'grab' => 0,
                'toll' => 0,
                'oil' => 0,
                'bus' => $busFare,
                'mode' => 'bus'
            ];
        }
    }

    /**
     * When a port is flagged as high-risk, find alternative ports
     * that serve the same destinations with safer conditions.
     */
    private function findAlternativeRoutes(Port $riskyPort, $currentSchedules): array
    {
        $alternatives = [];
        $now = now()->setTimezone('Asia/Singapore');

        // Get unique destination IDs from the current port's schedules
        $destinationIds = $currentSchedules
            ->pluck('destination_port_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($destinationIds)) {
            return [];
        }

        // Find other origin ports that also go to these same destinations
        $altSchedules = \App\Models\Schedule::with(['ferry', 'origin', 'origin.weatherData', 'destination'])
            ->whereIn('destination_port_id', $destinationIds)
            ->where('origin_port_id', '!=', $riskyPort->id)
            ->where('departure_time', '>=', $now)
            ->where('departure_time', '<=', $now->copy()->addDays(7)->endOfDay())
            ->orderBy('departure_time')
            ->get();

        // Group by origin port and assess each one
        $groupedByPort = $altSchedules->groupBy('origin_port_id');

        foreach ($groupedByPort as $portId => $portSchedules) {
            $altPort = $portSchedules->first()->origin;
            if (!$altPort) continue;

            // Check weather at the alternative port
            $altWeather = $altPort->weatherData()->latest()->first();
            $altRisk = $this->interpretRisk($altWeather);

            // Only recommend if it's safer than the current port
            if ($altRisk['color'] === 'red') continue;

            // Get the destinations served
            $destinations = $portSchedules->pluck('destination.name')->unique()->values();
            $cheapest = $portSchedules->min('price');
            $nextDeparture = $portSchedules->first();

            $alternatives[] = [
                'port'            => $altPort,
                'risk_status'     => $altRisk['status'],
                'risk_color'      => $altRisk['color'],
                'destinations'    => $destinations,
                'cheapest_price'  => $cheapest,
                'schedule_count'  => $portSchedules->count(),
                'next_departure'  => $nextDeparture ? [
                    'time'        => $nextDeparture->departure_time,
                    'ferry'       => $nextDeparture->ferry->name ?? 'N/A',
                    'destination' => $nextDeparture->destination->name ?? 'N/A',
                    'price'       => $nextDeparture->price,
                ] : null,
            ];
        }

        // Sort: safe ports first, then by number of available schedules
        usort($alternatives, function ($a, $b) {
            $colorOrder = ['green' => 0, 'yellow' => 1, 'red' => 2, 'gray' => 3];
            $aOrder = $colorOrder[$a['risk_color']] ?? 3;
            $bOrder = $colorOrder[$b['risk_color']] ?? 3;
            if ($aOrder !== $bOrder) return $aOrder - $bOrder;
            return $b['schedule_count'] - $a['schedule_count'];
        });

        return array_slice($alternatives, 0, 5);
    }

    /**
     * Convert a weather record into a colour-coded status label.
     */
    private function interpretRisk($weather): array
    {
        if (! $weather) {
            return ['status' => 'Unknown', 'color' => 'gray'];
        }

        // Prefer the AI-generated status when available
        if (! empty($weather->risk_status)) {
            $color = match ($weather->risk_status) {
                'High Risk' => 'red',
                'Caution'   => 'yellow',
                'Safe'      => 'green',
                default     => 'gray',
            };
            return ['status' => $weather->risk_status, 'color' => $color];
        }

        // Score-based fallback for older records
        $score = $weather->risk_score;
        if ($score < 30) {
            return ['status' => 'Safe', 'color' => 'green'];
        }
        if ($score < 70) {
            return ['status' => 'Caution', 'color' => 'yellow'];
        }
        return ['status' => 'High Risk', 'color' => 'red'];
    }

    /**
     * Manually store a weather reading for a port.
     */
    public function store(Request $request, Port $port)
    {
        $validated = $request->validate([
            'wind_speed'  => 'required|numeric|min:0',
            'wave_height' => 'required|numeric|min:0',
            'visibility'  => 'nullable|numeric|min:0',
            'tide_level'  => 'nullable|numeric',
        ]);

        $riskScore = 0;
        $status    = 'Safe';

        if ($validated['wind_speed'] > 40 || $validated['wave_height'] > 2.0) {
            $riskScore = 80;
            $status    = 'High Risk';
        } elseif ($validated['wind_speed'] > 25) {
            $riskScore = 50;
            $status    = 'Caution';
        }

        $weather = $port->weatherData()->create(array_merge($validated, [
            'recorded_at' => now(),
            'risk_score'  => $riskScore,
            'risk_status' => $status,
        ]));

        if ($status === 'High Risk') {
            \Illuminate\Support\Facades\Log::warning("🚨 HIGH RISK ALERT FOR PORT: {$port->name}");
        }

        if ($request->header('X-Internal-Call')) {
            return $weather;
        }

        return back()->with('success', 'Weather data saved successfully.');
    }

    /**
     * Pull fresh weather from external APIs for a single port.
     */
    public function refresh(Port $port, WeatherService $service)
    {
        $weather = $service->updateWeatherForPort($port);

        if (request()->wantsJson() || request()->header('X-Inertia')) {
            return back()->with(
                $weather ? 'success' : 'error',
                $weather ? 'Live analysis complete.' : 'Service unavailable. Try again.'
            );
        }



        return $weather;
    }

    /**
     * Queue weather updates for every port in the system.
     */
    public function refreshAll()
    {
        $ports = Port::all();

        foreach ($ports as $port) {
            FetchWeatherForPort::dispatch($port);
        }

        return response()->json([
            'success' => true,
            'message' => "Queued weather updates for {$ports->count()} ports.",
            'total'   => $ports->count(),
        ]);
    }

    // ------------------------------------------------------------------
    // Wind / wave grid data (for the Leaflet velocity overlay)
    // ------------------------------------------------------------------

    public function windData()
    {
        return Cache::remember('wind_grid_data', 3600, function () {
            return $this->buildSimulatedWindGrid();
        });
    }

    public function waveData()
    {
        return Cache::remember('wave_grid_data', 3600, function () {
            return $this->fetchWaveGridWithFallback();
        });
    }

    /**
     * Generate a simple global wind grid for the velocity overlay.
     */
    private function buildSimulatedWindGrid(): array
    {
        $nx = 360;
        $ny = 181;
        $size = $nx * $ny;

        return [
            [
                'header' => [
                    'parameterCategory' => 2, 'parameterNumber' => 2,
                    'nx' => $nx, 'ny' => $ny,
                    'lo1' => 0.0, 'la1' => 90.0,
                    'dx' => 1.0, 'dy' => 1.0,
                    'refTime' => now()->toIso8601String(),
                ],
                'data' => array_fill(0, $size, 10.0),
            ],
            [
                'header' => [
                    'parameterCategory' => 2, 'parameterNumber' => 3,
                    'nx' => $nx, 'ny' => $ny,
                    'lo1' => 0.0, 'la1' => 90.0,
                    'dx' => 1.0, 'dy' => 1.0,
                    'refTime' => now()->toIso8601String(),
                ],
                'data' => array_fill(0, $size, 5.0),
            ],
        ];
    }

    /**
     * Try real Open-Meteo wave data first; fall back to simulation.
     */
    private function fetchWaveGridWithFallback(): array
    {
        try {
            $grid = $this->fetchOpenMeteoWaveGrid();
            if ($this->gridHasNonZeroValues($grid)) {
                return $grid;
            }
        } catch (\Exception $e) {
            \Log::error("Wave data fetch failed: " . $e->getMessage());
        }

        \Log::warning("Open-Meteo wave data unavailable – using simulated fallback.");
        return $this->buildSimulatedWaveGrid();
    }

    private function gridHasNonZeroValues(array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        foreach (($data[0]['data'] ?? []) as $val) {
            if ($val != 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a simulated wave grid (global, SW flow pattern).
     */
    private function buildSimulatedWaveGrid(): array
    {
        $nx = 360;
        $ny = 181;
        $size = $nx * $ny;

        $uData = array_fill(0, $size, 0.0);
        $vData = array_fill(0, $size, 0.0);

        for ($latIdx = 0; $latIdx < $ny; $latIdx++) {
            $lat = 90.0 + ($latIdx * -1.0);
            for ($lonIdx = 0; $lonIdx < $nx; $lonIdx++) {
                $i = ($latIdx * $nx) + $lonIdx;
                $height = 4.0 + (1.0 * sin($lat * 0.2));
                $rad = deg2rad(225);
                $uData[$i] = round(-$height * sin($rad), 3);
                $vData[$i] = round(-$height * cos($rad), 3);
            }
        }

        $header = [
            'parameterCategory' => 2,
            'parameterNumber'   => 2,
            'nx' => $nx, 'ny' => $ny,
            'lo1' => -180, 'la1' => 90, 'lo2' => 179, 'la2' => -90,
            'dx' => 1.0, 'dy' => -1.0,
            'refTime' => now()->setTimezone('UTC')->toIso8601String(),
        ];

        return [
            ['header' => $header, 'data' => $uData],
            ['header' => array_merge($header, ['parameterNumber' => 3]), 'data' => $vData],
        ];
    }

    /**
     * Fetch real wave data from Open-Meteo for the ASEAN region and
     * map it onto a 1° global grid.
     */
    private function fetchOpenMeteoWaveGrid(): array
    {
        $nx = 360;
        $ny = 181;
        $size = $nx * $ny;
        $uData = array_fill(0, $size, 0.0);
        $vData = array_fill(0, $size, 0.0);

        // Build coordinate list for the region of interest
        $lats = [];
        $lons = [];
        for ($lat = 14; $lat >= -2; $lat -= 1.0) {
            for ($lon = 94; $lon <= 126; $lon += 1.0) {
                $lats[] = $lat;
                $lons[] = $lon;
            }
        }

        $chunkSize = 60;
        $results   = [];

        for ($i = 0; $i < count($lats); $i += $chunkSize) {
            $chunkLats = array_slice($lats, $i, $chunkSize);
            $chunkLons = array_slice($lons, $i, $chunkSize);

            $response = Http::timeout(5)->get('https://marine-api.open-meteo.com/v1/marine', [
                'latitude'      => implode(',', $chunkLats),
                'longitude'     => implode(',', $chunkLons),
                'hourly'        => 'wave_height,wave_direction',
                'forecast_days' => 1,
            ]);

            if ($response->failed() || $response->status() === 429) {
                \Log::warning("Open-Meteo rate-limited or failed", ['status' => $response->status()]);
                return [];
            }

            $chunk = $response->json();
            if (! is_array($chunk)) {
                continue;
            }
            if (! isset($chunk[0])) {
                $chunk = [$chunk];
            }

            $results = array_merge($results, $chunk);
            usleep(250_000); // 250 ms courtesy delay
        }

        // Map results onto the global grid
        $hour   = (int) gmdate('G');
        $mapped = 0;

        foreach ($results as $idx => $point) {
            if (! isset($lats[$idx])) {
                break;
            }
            if (! isset($point['hourly']['wave_height'][$hour])) {
                continue;
            }

            $height = $point['hourly']['wave_height'][$hour];
            $dir    = $point['hourly']['wave_direction'][$hour];
            if (! $height || $height <= 0) {
                continue;
            }

            $latIdx = (int) round(90 - $lats[$idx]);
            $lonIdx = (int) round($lons[$idx] + 180);
            if ($lonIdx >= $nx) {
                $lonIdx = 0;
            }

            $arrayIdx = ($latIdx * $nx) + $lonIdx;
            if ($arrayIdx < 0 || $arrayIdx >= $size) {
                continue;
            }

            $rad = deg2rad($dir);
            $uData[$arrayIdx] = round(-$height * sin($rad), 3);
            $vData[$arrayIdx] = round(-$height * cos($rad), 3);
            $mapped++;
        }

        if ($mapped < 5) {
            return [];
        }

        $header = [
            'parameterCategory' => 2, 'parameterNumber' => 2,
            'nx' => $nx, 'ny' => $ny,
            'lo1' => -180, 'la1' => 90,
            'dx' => 1.0, 'dy' => -1.0,
            'refTime' => now()->toIso8601String(),
        ];

        return [
            ['header' => $header, 'data' => $uData],
            ['header' => array_merge($header, ['parameterNumber' => 3]), 'data' => $vData],
        ];
    }
}

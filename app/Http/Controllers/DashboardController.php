<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use SplFileObject;

class DashboardController extends Controller
{
    /**
     * Render the main dashboard with admin stats (when applicable).
     */
    public function index()
    {
        $user = auth()->user();
        $adminStats = null;

        if ($user && $user->is_admin) {
            $adminStats = [
                'total_users'     => \App\Models\User::count(),
                'total_ferries'   => \App\Models\Ferry::count(),
                'active_voyages'  => \App\Models\Schedule::where('departure_time', '>=', now())->count(),
                'total_schedules' => \App\Models\Schedule::count(),
                'total_ports'     => \App\Models\Port::count(),
            ];
        }

        $ports = \App\Models\Port::whereHas('departures')->orWhereHas('arrivals')->with('latestWeather')->get();

        // --- DUMMY DATA INJECTION (MERSING ONLY) ---
        foreach ($ports as $port) {
            if (str_contains(strtolower($port->name), 'mersing')) {
                $port->setRelation('latestWeather', new \App\Models\WeatherData([
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
                ]));
            }
        }
        // -------------------------------------------

        return Inertia::render('Dashboard', [
            'ports'           => $ports,
            'adminStats'      => $adminStats,
            'systemLogs'      => [],
        ]);
    }

    /**
     * Return the latest 50 log entries for the admin panel (AJAX).
     */
    public function systemLogs()
    {
        if (! auth()->user()?->is_admin) {
            return response()->json([]);
        }

        $logPath = storage_path('logs/laravel.log');
        $entries = [];

        if (! file_exists($logPath)) {
            return response()->json([]);
        }

        try {
            $file = new SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            if ($totalLines === 0) {
                return response()->json([]);
            }

            $file->seek(max(0, $totalLines - 50));
            $lines = [];

            while (! $file->eof()) {
                $line = trim($file->current());
                if ($line) {
                    $lines[] = $line;
                }
                $file->next();
            }

            foreach (array_reverse($lines) as $line) {
                if (preg_match('/^\[(?<date>.*?)\] (?<env>\w+)\.(?<level>\w+): (?<message>.*)/', $line, $m)) {
                    $entries[] = [
                        'date'    => $m['date'],
                        'level'   => $m['level'],
                        'message' => $m['message'],
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log file unreadable – return empty
        }

        return response()->json($entries);
    }

    /**
     * Geo-intelligence: analyse nearby ports relative to a coordinate.
     */
    public function geoAnalysis(\Illuminate\Http\Request $request, \App\Services\GeoIntelligenceService $geo)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        return response()->json(
            $geo->analyzeLocation($request->lat, $request->lng)
        );
    }

    /**
     * Geo-intelligence: scan weather along a route's mid-sea waypoints.
     */
    public function analyzeRoute(\Illuminate\Http\Request $request, \App\Services\GeoIntelligenceService $geo)
    {
        $request->validate([
            'origin_id'      => 'required|exists:ports,id',
            'destination_id' => 'required|exists:ports,id',
        ]);

        $origin = \App\Models\Port::find($request->origin_id);
        $dest   = \App\Models\Port::find($request->destination_id);

        return response()->json(
            $geo->analyzeRouteViability($origin, $dest)
        );
    }
}

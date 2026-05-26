<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use App\Models\Port;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    /**
     * List schedules with optional geolocation, date, destination,
     * and time-of-day filters.
     */
    public function index(Request $request)
    {
        $latitude  = $request->query('latitude');
        $longitude = $request->query('longitude');
        $nearestPorts = collect();
        $originPortIds = [];

        // Geo-filter: find the 3 closest ports to the user
        if ($latitude && $longitude) {
            $nearestPorts = Port::select('*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$latitude, $longitude, $latitude]
                )
                ->orderBy('distance')
                ->limit(3)
                ->get();

            if ($nearestPorts->isNotEmpty()) {
                $originPortIds = $nearestPorts->pluck('id')->toArray();
            }
        }

        $query = Schedule::with(['ferry', 'origin', 'destination']);

        // Date (defaults to today)
        $date = $request->query('date', now()->toDateString());
        $query->whereDate('departure_time', $date);

        if ($request->filled('origin_port_id')) {
            $originPortIds[] = $request->query('origin_port_id');
        }

        if (! empty($originPortIds)) {
            $query->whereIn('origin_port_id', $originPortIds);
        }

        if ($request->filled('destination_port_id')) {
            $query->where('destination_port_id', $request->query('destination_port_id'));
        }

        // Time-of-day filter
        if ($request->filled('time_of_day')) {
            $tod = $request->query('time_of_day');
            $ranges = [
                'morning'   => ['00:00:00', '12:00:00'],
                'afternoon' => ['12:00:00', '18:00:00'],
                'evening'   => ['18:00:00', '23:59:59'],
            ];
            if (isset($ranges[$tod])) {
                $query->whereTime('departure_time', '>=', $ranges[$tod][0])
                      ->whereTime('departure_time', $tod === 'evening' ? '<=' : '<', $ranges[$tod][1]);
            }
        }

        $schedules = $query->orderBy('departure_time')->get();

        // Cached reference data
        $ferries = Cache::remember('ferries.list_all', 3600, fn () => Ferry::all());

        $activePorts = Cache::remember('ports.active', 3600, function () {
            return Port::whereHas('departures')->orWhereHas('arrivals')->with('latestWeather')->get();
        });

        // Build port-keyed weather lookup for schedule badges
        $portWeather = [];
        foreach ($activePorts as $port) {
            if ($port->latestWeather) {
                $portWeather[$port->id] = [
                    'wind_speed'  => $port->latestWeather->wind_speed,
                    'wave_height' => $port->latestWeather->wave_height,
                    'visibility'  => $port->latestWeather->visibility,
                    'risk_score'  => $port->latestWeather->risk_score ?? null,
                    'risk_status' => $port->latestWeather->risk_status ?? null,
                ];
            }
        }

        // Next 10 upcoming departures for the live board
        $upcomingDepartures = Schedule::with(['ferry', 'origin', 'destination'])
            ->where('departure_time', '>=', now())
            ->orderBy('departure_time')
            ->limit(10)
            ->get();

        // Distinct routes for the Travel Window Recommender picker
        $availableRoutes = Cache::remember('routes.available', 3600, function () {
            return Schedule::select('origin_port_id', 'destination_port_id')
                ->with(['origin:id,name', 'destination:id,name'])
                ->where('departure_time', '>=', now())
                ->distinct()
                ->get()
                ->map(fn ($s) => [
                    'origin_id'   => $s->origin_port_id,
                    'origin_name' => $s->origin->name ?? '',
                    'dest_id'     => $s->destination_port_id,
                    'dest_name'   => $s->destination->name ?? '',
                    'label'       => ($s->origin->name ?? '') . ' → ' . ($s->destination->name ?? ''),
                ])
                ->unique('label')
                ->values();
        });

        return Inertia::render('Schedules/Index', [
            'schedules'          => $schedules,
            'ferries'            => $ferries,
            'ports'              => $activePorts,
            'nearestPorts'       => $nearestPorts,
            'userLocation'       => $latitude && $longitude ? ['lat' => $latitude, 'lng' => $longitude] : null,
            'filters'            => $request->only(['date', 'origin_port_id', 'destination_port_id', 'time_of_day', 'latitude', 'longitude', 'state']),
            'initialDate'        => $date,
            'portWeather'        => $portWeather,
            'upcomingDepartures' => $upcomingDepartures,
            'availableRoutes'    => $availableRoutes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ferry_id'            => 'required|exists:ferries,id',
            'origin_port_id'      => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            'departure_time'      => 'required|date',
            'arrival_time'        => 'required|date|after:departure_time',
            'price'               => 'required|numeric|min:0',
            'total_seats'         => 'nullable|integer|min:1',
        ]);

        // Default total_seats from ferry capacity when not specified
        if (empty($validated['total_seats'])) {
            $ferry = Ferry::find($validated['ferry_id']);
            $validated['total_seats'] = $ferry?->capacity;
        }

        Schedule::create($validated);
        Cache::forget('ports.active');

        return redirect()->back()->with('success', 'Schedule created successfully.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'ferry_id'            => 'required|exists:ferries,id',
            'origin_port_id'      => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            'departure_time'      => 'required|date',
            'arrival_time'        => 'required|date|after:departure_time',
            'price'               => 'required|numeric|min:0',
        ]);

        $schedule->update($validated);

        return redirect()->back()->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        Cache::forget('ports.active');

        return redirect()->back()->with('success', 'Schedule deleted.');
    }

    /**
     * Re-generate today's recurring schedules from templates.
     */
    public function generateDaily()
    {
        \Illuminate\Support\Facades\Artisan::call('schedules:generate-daily');

        return redirect()->back()->with('success', 'Daily schedule generation triggered successfully.');
    }
}

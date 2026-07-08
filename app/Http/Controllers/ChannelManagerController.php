<?php

namespace App\Http\Controllers;

use App\Models\ExternalBooking;
use App\Models\Operator;
use App\Models\Schedule;
use App\Services\ChannelManagerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChannelManagerController extends Controller
{
    private ChannelManagerService $channelManager;

    public function __construct(ChannelManagerService $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Show the channel management dashboard with availability overview.
     */
    public function index(Request $request)
    {
        $operators = Operator::withCount('ferries')->get();

        // Region mapping for Malaysian ports (derived from port locations)
        $regionMap = [
            'Perlis'      => ['Kuala Perlis'],
            'Kedah'       => ['Kuala Kedah', 'Langkawi', 'Kuah'],
            'Penang'      => ['Georgetown', 'Butterworth'],
            'Perak'       => ['Lumut', 'Pangkor'],
            'Selangor'    => ['Port Klang', 'Pulau Ketam'],
            'Melaka'      => ['Melaka City'],
            'Johor'       => ['Mersing', 'Iskandar Puteri'],
            'Pahang'      => ['Kuala Rompin', 'Tioman Island'],
            'Terengganu'  => ['Kuala Terengganu', 'Setiu', 'Besut', 'Marang', 'Kapas Island', 'Redang Island', 'Perhentian Island'],
            'Indonesia'   => ['Dumai', 'Bengkalis', 'Batam'],
        ];

        // All active ports for the port filter dropdown
        $ports = \App\Models\Port::whereHas('departures')
            ->orWhereHas('arrivals')
            ->orderBy('name')
            ->get(['id', 'name', 'location']);

        // Build a region → port IDs lookup so the frontend can filter the port dropdown
        $regionPortIds = [];
        foreach ($regionMap as $region => $keywords) {
            $regionPortIds[$region] = \App\Models\Port::where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('location', 'like', "%{$keyword}%")
                      ->orWhere('name', 'like', "%{$keyword}%");
                }
            })->pluck('id')->toArray();
        }

        // Build schedule query with filters
        $date = $request->query('date', now()->toDateString());
        $query = Schedule::with(['ferry', 'origin', 'destination'])
            ->whereDate('departure_time', $date)
            ->orderBy('departure_time');

        // Region filter: find port IDs that belong to the selected region
        if ($request->filled('region')) {
            $regionKeywords = $regionMap[$request->query('region')] ?? [];
            if (! empty($regionKeywords)) {
                $regionPortIds = \App\Models\Port::where(function ($q) use ($regionKeywords) {
                    foreach ($regionKeywords as $keyword) {
                        $q->orWhere('location', 'like', "%{$keyword}%")
                          ->orWhere('name', 'like', "%{$keyword}%");
                    }
                })->pluck('id')->toArray();

                $query->where(function ($q) use ($regionPortIds) {
                    $q->whereIn('origin_port_id', $regionPortIds)
                      ->orWhereIn('destination_port_id', $regionPortIds);
                });
            }
        }

        // Port filter: show schedules departing from or arriving at this port
        if ($request->filled('port_id')) {
            $portId = $request->query('port_id');
            $query->where(function ($q) use ($portId) {
                $q->where('origin_port_id', $portId)
                  ->orWhere('destination_port_id', $portId);
            });
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

        $schedules = $query->get()->map(function ($schedule) {
            $capacity  = $schedule->total_seats ?? $schedule->ferry->capacity ?? 0;
            $available = $schedule->availableSeats();

            return [
                'id'              => $schedule->id,
                'ferry'           => $schedule->ferry->name,
                'operator'        => $schedule->ferry->operator,
                'origin'          => $schedule->origin->name,
                'origin_location' => $schedule->origin->location,
                'destination'     => $schedule->destination->name,
                'dest_location'   => $schedule->destination->location,
                'departure_time'  => $schedule->departure_time->format('h:i A'),
                'departure_raw'   => $schedule->departure_time->toIso8601String(),
                'price'           => $schedule->price,
                'capacity'        => $capacity,
                'booked'          => $schedule->booked_seats,
                'available'       => $available,
                'status'          => $schedule->status,
                'source'          => $schedule->source,
                'occupancy_pct'   => $capacity > 0
                    ? round($schedule->booked_seats / $capacity * 100, 1)
                    : 0,
            ];
        });

        // Stats based on filtered results
        $totalCapacity  = $schedules->sum('capacity');
        $totalBooked    = $schedules->sum('booked');

        return Inertia::render('Admin/ChannelManager', [
            'operators'     => $operators,
            'schedules'     => $schedules,
            'ports'         => $ports,
            'regions'       => array_keys($regionMap),
            'regionPortIds' => $regionPortIds,
            'filters'       => $request->only(['date', 'region', 'port_id', 'time_of_day']),
            'stats'         => [
                'total_operators' => $operators->count(),
                'total_capacity'  => $totalCapacity,
                'total_booked'    => $totalBooked,
                'total_available' => $totalCapacity - $totalBooked,
                'occupancy_pct'   => $totalCapacity > 0
                    ? round($totalBooked / $totalCapacity * 100, 1)
                    : 0,
            ],
        ]);
    }

    /**
     * Detailed availability breakdown for a single schedule.
     */
    public function scheduleDetail(Schedule $schedule)
    {
        return response()->json(
            $this->channelManager->getAvailabilitySummary($schedule)
        );
    }

    /**
     * Trigger a sync with all enabled operators.
     */
    public function syncAll()
    {
        $results = $this->channelManager->syncAll();

        return redirect()->back()->with('success', 'Channel sync completed. ' . $this->formatSyncResults($results));
    }

    /**
     * Trigger a sync with a specific operator.
     */
    public function syncOperator(Operator $operator)
    {
        $result = $this->channelManager->syncOperator($operator);

        $msg = "Synced {$result['synced']} schedules and {$result['bookings']} bookings from {$operator->name}.";

        if (! empty($result['errors'])) {
            $msg .= ' Errors: ' . implode(', ', $result['errors']);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Store a new operator profile.
     */
    public function storeOperator(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:20|unique:operators,code',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'api_endpoint'  => 'nullable|url|max:255',
            'api_key'       => 'nullable|string|max:255',
            'sync_enabled'  => 'boolean',
        ]);

        Operator::create($validated);

        return redirect()->back()->with('success', "Operator '{$validated['name']}' added.");
    }

    /**
     * Update an operator profile.
     */
    public function updateOperator(Request $request, Operator $operator)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:20|unique:operators,code,' . $operator->id,
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'api_endpoint'  => 'nullable|url|max:255',
            'api_key'       => 'nullable|string|max:255',
            'sync_enabled'  => 'boolean',
        ]);

        $operator->update($validated);

        return redirect()->back()->with('success', "Operator '{$operator->name}' updated.");
    }

    /**
     * Delete an operator.
     */
    public function destroyOperator(Operator $operator)
    {
        $operator->delete();

        return redirect()->back()->with('success', 'Operator removed.');
    }

    /**
     * Record an external/walk-in booking against a schedule.
     */
    public function recordExternalBooking(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'       => 'required|exists:schedules,id',
            'platform'          => 'required|string|max:50',
            'external_ref'      => 'required|string|max:100',
            'quantity'          => 'required|integer|min:1',
            'passenger_name'    => 'nullable|string|max:255',
            'passenger_contact' => 'nullable|string|max:255',
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);

        try {
            $this->channelManager->recordExternalBooking(
                $schedule,
                $validated['platform'],
                $validated['external_ref'],
                $validated['quantity'],
                $validated['passenger_name'] ?? null,
                $validated['passenger_contact'] ?? null
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'External booking recorded.');
    }

    /**
     * Cancel an external booking and free up seats.
     */
    public function cancelExternalBooking(ExternalBooking $externalBooking)
    {
        $schedule = $externalBooking->schedule;

        $externalBooking->update(['status' => 'cancelled']);
        $schedule->recalculateSeats();

        return redirect()->back()->with('success', 'External booking cancelled, seats released.');
    }

    /**
     * Export schedules as an iCal (.ics) feed.
     */
    public function icalFeed(Request $request)
    {
        $query = Schedule::with(['ferry', 'origin', 'destination']);

        // Optional filtering
        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->query('date'));
        } else {
            // Default: next 7 days
            $query->whereBetween('departure_time', [now(), now()->addDays(7)]);
        }

        if ($request->filled('ferry_id')) {
            $query->where('ferry_id', $request->query('ferry_id'));
        }

        $schedules = $query->orderBy('departure_time')->get();
        $icsContent = $this->channelManager->generateICalFeed($schedules);

        return response($icsContent, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="ferrycast-schedules.ics"',
        ]);
    }

    /**
     * Format sync results into a readable string.
     */
    private function formatSyncResults(array $results): string
    {
        $parts = [];

        foreach ($results as $code => $result) {
            $parts[] = "{$code}: {$result['synced']} schedules, {$result['bookings']} bookings";
        }

        return implode(' | ', $parts);
    }
}

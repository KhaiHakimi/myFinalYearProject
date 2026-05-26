<?php

namespace App\Services;

use App\Models\ExternalBooking;
use App\Models\Operator;
use App\Models\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelManagerService
{
    /**
     * Sync schedules and bookings from all enabled operators.
     * This pulls data from operator APIs and updates local records.
     */
    public function syncAll(): array
    {
        $results = [];

        $operators = Operator::where('sync_enabled', true)
            ->whereNotNull('api_endpoint')
            ->get();

        foreach ($operators as $operator) {
            $results[$operator->code] = $this->syncOperator($operator);
        }

        return $results;
    }

    /**
     * Pull schedules and external bookings from one operator's API.
     */
    public function syncOperator(Operator $operator): array
    {
        $synced    = 0;
        $errors    = [];
        $bookings  = 0;

        try {
            // Fetch schedules from the operator's endpoint
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $operator->api_key,
                    'Accept'        => 'application/json',
                ])
                ->get($operator->api_endpoint . '/schedules', [
                    'date' => now()->toDateString(),
                ]);

            if ($response->failed()) {
                Log::warning("Channel sync failed for {$operator->code}: HTTP {$response->status()}");
                return ['synced' => 0, 'bookings' => 0, 'errors' => ["HTTP {$response->status()}"]];
            }

            $scheduleData = $response->json('schedules', []);

            foreach ($scheduleData as $item) {
                try {
                    $schedule = $this->upsertSchedule($operator, $item);
                    $synced++;

                    // Sync bookings attached to schedule (if operator sent them)
                    if (isset($item['bookings'])) {
                        foreach ($item['bookings'] as $extBooking) {
                            $this->upsertExternalBooking($schedule, $operator->code, $extBooking);
                            $bookings++;
                        }
                        $schedule->recalculateSeats();
                    }
                } catch (\Exception $e) {
                    $ref = $item['external_ref'] ?? '?';
                    $errors[] = "Schedule {$ref}: {$e->getMessage()}";
                }
            }

            $operator->update(['last_synced_at' => now()]);
        } catch (\Exception $e) {
            Log::error("Channel sync error for {$operator->code}: {$e->getMessage()}");
            $errors[] = $e->getMessage();
        }

        return compact('synced', 'bookings', 'errors');
    }

    /**
     * Map an incoming schedule payload to a local Schedule record.
     * If the external_ref already exists, update it; otherwise create a new one.
     */
    private function upsertSchedule(Operator $operator, array $data): Schedule
    {
        // Find the ferry by its name or external reference
        $ferry = $operator->ferries()
            ->where('name', $data['ferry_name'] ?? '')
            ->first();

        if (! $ferry) {
            throw new \RuntimeException("Unknown ferry: " . ($data['ferry_name'] ?? 'N/A'));
        }

        return Schedule::updateOrCreate(
            [
                'external_ref' => $data['external_ref'],
                'source'       => 'operator_api',
            ],
            [
                'ferry_id'            => $ferry->id,
                'origin_port_id'      => $data['origin_port_id'],
                'destination_port_id' => $data['destination_port_id'],
                'departure_time'      => $data['departure_time'],
                'arrival_time'        => $data['arrival_time'],
                'price'               => $data['price'] ?? 0,
                'total_seats'         => $data['total_seats'] ?? $ferry->capacity,
                'booked_seats'        => $data['booked_seats'] ?? 0,
                'status'              => $data['status'] ?? 'open',
            ]
        );
    }

    /**
     * Import or update a booking that was made on an external platform.
     */
    private function upsertExternalBooking(Schedule $schedule, string $platform, array $data): ExternalBooking
    {
        return ExternalBooking::updateOrCreate(
            [
                'platform'     => $platform,
                'external_ref' => $data['external_ref'],
            ],
            [
                'schedule_id'       => $schedule->id,
                'quantity'          => $data['quantity'] ?? 1,
                'passenger_name'    => $data['passenger_name'] ?? null,
                'passenger_contact' => $data['passenger_contact'] ?? null,
                'status'            => $data['status'] ?? 'confirmed',
            ]
        );
    }

    /**
     * Record a walk-in or phone booking against a schedule.
     * Used by operators who accept bookings outside the website.
     */
    public function recordExternalBooking(
        Schedule $schedule,
        string $platform,
        string $externalRef,
        int $quantity,
        ?string $passengerName = null,
        ?string $passengerContact = null
    ): ExternalBooking {
        if (! $schedule->hasAvailableSeats($quantity)) {
            throw new \RuntimeException(
                "Not enough seats: {$schedule->availableSeats()} available, {$quantity} requested."
            );
        }

        $booking = ExternalBooking::create([
            'schedule_id'       => $schedule->id,
            'platform'          => $platform,
            'external_ref'      => $externalRef,
            'quantity'          => $quantity,
            'passenger_name'    => $passengerName,
            'passenger_contact' => $passengerContact,
            'status'            => 'confirmed',
        ]);

        $schedule->recalculateSeats();

        return $booking;
    }

    /**
     * Generate an iCal feed for a schedule or a set of schedules.
     */
    public function generateICalFeed($schedules): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Ferrycast//Channel Manager//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Ferrycast Ferry Schedules',
        ];

        foreach ($schedules as $schedule) {
            $schedule->loadMissing(['ferry', 'origin', 'destination']);

            $uid        = "schedule-{$schedule->id}@ferrycast";
            $dtStart    = $schedule->departure_time->format('Ymd\THis');
            $dtEnd      = $schedule->arrival_time->format('Ymd\THis');
            $summary    = $schedule->origin->name . ' → ' . $schedule->destination->name;
            $desc       = sprintf(
                'Ferry: %s | Price: RM %.2f | Seats left: %d',
                $schedule->ferry->name,
                $schedule->price,
                $schedule->availableSeats()
            );
            $location   = $schedule->origin->name;
            $created    = $schedule->created_at->format('Ymd\THis\Z');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = "UID:{$uid}";
            $lines[] = "DTSTART:{$dtStart}";
            $lines[] = "DTEND:{$dtEnd}";
            $lines[] = "SUMMARY:{$summary}";
            $lines[] = "DESCRIPTION:{$desc}";
            $lines[] = "LOCATION:{$location}";
            $lines[] = "DTSTAMP:{$created}";
            $lines[] = "STATUS:CONFIRMED";
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /**
     * Pull availability from all channels, recalculate, and return a summary.
     */
    public function getAvailabilitySummary(Schedule $schedule): array
    {
        $schedule->loadMissing(['ferry', 'origin', 'destination', 'bookings', 'externalBookings']);

        $internalPaid = $schedule->bookings->where('payment_status', 'paid')->sum('quantity');
        $internalPending = $schedule->bookings->where('payment_status', 'pending')->sum('quantity');

        $externalByPlatform = $schedule->externalBookings
            ->where('status', 'confirmed')
            ->groupBy('platform')
            ->map(fn ($group) => $group->sum('quantity'));

        $totalExternal = $externalByPlatform->sum();
        $capacity      = $schedule->total_seats ?? $schedule->ferry->capacity ?? 0;

        return [
            'schedule_id'     => $schedule->id,
            'route'           => $schedule->origin->name . ' → ' . $schedule->destination->name,
            'departure'       => $schedule->departure_time->format('d M Y, h:i A'),
            'ferry'           => $schedule->ferry->name,
            'capacity'        => $capacity,
            'internal_paid'   => $internalPaid,
            'internal_pending'=> $internalPending,
            'external'        => $externalByPlatform->toArray(),
            'total_booked'    => $internalPaid + $totalExternal,
            'available'       => max(0, $capacity - $internalPaid - $totalExternal),
            'status'          => $schedule->status,
            'occupancy_pct'   => $capacity > 0
                ? round(($internalPaid + $totalExternal) / $capacity * 100, 1)
                : 0,
        ];
    }
}

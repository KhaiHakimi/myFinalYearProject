<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'ferry_id',
        'origin_port_id',
        'destination_port_id',
        'departure_time',
        'arrival_time',
        'price',
        'total_seats',
        'booked_seats',
        'status',
        'external_ref',
        'source',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time'   => 'datetime',
        'total_seats'    => 'integer',
        'booked_seats'   => 'integer',
    ];

    public function ferry()
    {
        return $this->belongsTo(Ferry::class);
    }

    public function origin()
    {
        return $this->belongsTo(Port::class, 'origin_port_id');
    }

    public function destination()
    {
        return $this->belongsTo(Port::class, 'destination_port_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function externalBookings()
    {
        return $this->hasMany(ExternalBooking::class);
    }

    /**
     * How many seats are currently available for new bookings.
     * Falls back to the ferry's total capacity when total_seats is not set.
     */
    public function availableSeats(): int
    {
        $capacity = $this->total_seats ?? $this->ferry?->capacity ?? 0;

        return max(0, $capacity - $this->booked_seats);
    }

    /**
     * Whether a given ticket quantity can still be booked.
     */
    public function hasAvailableSeats(int $quantity = 1): bool
    {
        return $this->status === 'open' && $this->availableSeats() >= $quantity;
    }

    /**
     * Recalculate booked_seats from confirmed bookings across all channels,
     * then flip status to 'full' when the ferry is at capacity.
     */
    public function recalculateSeats(): void
    {
        $internal = $this->bookings()
            ->where('payment_status', 'paid')
            ->sum('quantity');

        $external = $this->externalBookings()
            ->where('status', 'confirmed')
            ->sum('quantity');

        $this->booked_seats = $internal + $external;

        $capacity = $this->total_seats ?? $this->ferry?->capacity ?? 0;

        if ($capacity > 0 && $this->booked_seats >= $capacity) {
            $this->status = 'full';
        } elseif ($this->status === 'full') {
            // Reopen if seats freed (e.g. cancellation)
            $this->status = 'open';
        }

        $this->save();
    }
}

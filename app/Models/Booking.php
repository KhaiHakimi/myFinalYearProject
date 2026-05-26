<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'schedule_id',
        'passenger_name',
        'passenger_email',
        'passenger_phone',
        'quantity',
        'total_amount',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent',
        'payment_status',
        'booking_reference',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Generate a unique booking reference like FC-20260222-A1B2C3
     */
    public static function generateReference(): string
    {
        do {
            $ref = 'FC-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('booking_reference', $ref)->exists());

        return $ref;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}

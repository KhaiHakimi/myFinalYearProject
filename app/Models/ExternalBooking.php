<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalBooking extends Model
{
    protected $fillable = [
        'schedule_id',
        'platform',
        'external_ref',
        'quantity',
        'passenger_name',
        'passenger_contact',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}

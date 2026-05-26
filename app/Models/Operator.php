<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact_email',
        'contact_phone',
        'api_endpoint',
        'api_key',
        'sync_enabled',
        'last_synced_at',
    ];

    protected $casts = [
        'sync_enabled'   => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * All ferries operated by this company.
     */
    public function ferries()
    {
        return $this->hasMany(Ferry::class);
    }

    /**
     * All schedules across this operator's entire fleet.
     */
    public function schedules()
    {
        return $this->hasManyThrough(Schedule::class, Ferry::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ferry extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'operator',
        'operator_id',
        'image_path',
        'rating',
        'reviews_count',
        'length_ft',
        'amenities',
        'price',
        'description',
        'booking_url',
        'ticket_type',
        'google_place_id',
    ];

    protected $casts = [
        'amenities' => 'array',
        'rating' => 'decimal:1',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The operator company that runs this ferry.
     */
    public function operatorCompany()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripTrack extends Model
{
    protected $fillable = [
        'job_order_id', 'driver_id', 'lat', 'lng',
        'speed_kmh', 'heading', 'distance_from_prev_km', 'recorded_at',
    ];

    protected $casts = [
        'lat'                   => 'decimal:7',
        'lng'                   => 'decimal:7',
        'speed_kmh'             => 'decimal:2',
        'heading'               => 'decimal:2',
        'distance_from_prev_km' => 'decimal:3',
        'recorded_at'           => 'datetime',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
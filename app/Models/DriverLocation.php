<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id', 'job_order_id', 'lat', 'lng',
        'accuracy', 'speed_kmh', 'heading',
        'battery_level', 'is_online', 'recorded_at',
    ];

    protected $casts = [
        'lat'          => 'decimal:7',
        'lng'          => 'decimal:7',
        'accuracy'     => 'decimal:2',
        'speed_kmh'    => 'decimal:2',
        'heading'      => 'decimal:2',
        'is_online'    => 'boolean',
        'recorded_at'  => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }
}
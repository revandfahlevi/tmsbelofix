<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceEvent extends Model
{
    protected $fillable = [
        'geofence_id', 'driver_id', 'job_order_id',
        'event_type', 'lat', 'lng', 'occurred_at',
    ];

    protected $casts = [
        'lat'         => 'decimal:7',
        'lng'         => 'decimal:7',
        'occurred_at' => 'datetime',
    ];

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteWaypoint extends Model
{
    protected $fillable = [
        'route_plan_id', 'sequence', 'label', 'address',
        'lat', 'lng', 'type', 'estimated_stop_minutes',
        'estimated_arrival_at', 'actual_arrival_at', 'status', 'notes',
    ];

    protected $casts = [
        'lat'                   => 'decimal:7',
        'lng'                   => 'decimal:7',
        'estimated_arrival_at'  => 'datetime',
        'actual_arrival_at'     => 'datetime',
    ];

    public function routePlan(): BelongsTo
    {
        return $this->belongsTo(RoutePlan::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutePlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_number', 'job_order_id', 'created_by', 'driver_id',
        'origin_address', 'origin_lat', 'origin_lng',
        'destination_address', 'destination_lat', 'destination_lng',
        'total_distance_km', 'estimated_duration_minutes',
        'estimated_fuel_liters', 'estimated_toll_cost',
        'estimated_fuel_cost', 'total_estimated_cost',
        'status', 'route_type', 'waypoints', 'polyline_points', 'notes',
    ];

    protected $casts = [
        'origin_lat'                  => 'decimal:7',
        'origin_lng'                  => 'decimal:7',
        'destination_lat'             => 'decimal:7',
        'destination_lng'             => 'decimal:7',
        'total_distance_km'           => 'decimal:3',
        'estimated_fuel_liters'       => 'decimal:2',
        'estimated_toll_cost'         => 'decimal:2',
        'estimated_fuel_cost'         => 'decimal:2',
        'total_estimated_cost'        => 'decimal:2',
        'waypoints'                   => 'array',
        'polyline_points'             => 'array',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function routeWaypoints(): HasMany
    {
        return $this->hasMany(RouteWaypoint::class)->orderBy('sequence');
    }

    public static function generateRouteNumber(): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $count = static::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count();
        return sprintf('RT-%s%s-%05d', $year, $month, $count + 1);
    }

    public function canTransitionTo(string $new): bool
    {
        $map = [
            'draft'     => ['approved', 'cancelled'],
            'approved'  => ['active', 'cancelled'],
            'active'    => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        return in_array($new, $map[$this->status] ?? []);
    }

    // Hitung total biaya estimasi
    public function recalculateCost(float $fuelPricePerLiter = 10000): void
    {
        $fuelCost  = ($this->estimated_fuel_liters ?? 0) * $fuelPricePerLiter;
        $tollCost  = $this->estimated_toll_cost ?? 0;

        $this->update([
            'estimated_fuel_cost'  => $fuelCost,
            'total_estimated_cost' => $fuelCost + $tollCost,
        ]);
    }
}
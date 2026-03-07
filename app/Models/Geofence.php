<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geofence extends Model
{
    protected $fillable = [
        'name', 'type', 'center_lat', 'center_lng',
        'radius_meters', 'polygon_points', 'trigger', 'is_active',
    ];

    protected $casts = [
        'center_lat'     => 'decimal:7',
        'center_lng'     => 'decimal:7',
        'polygon_points' => 'array',
        'is_active'      => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(GeofenceEvent::class);
    }

    // Cek apakah koordinat masuk dalam geofence (circle)
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->type !== 'circle') return false;

        $distance = $this->haversineDistance(
            $this->center_lat, $this->center_lng, $lat, $lng
        );

        return $distance <= $this->radius_meters;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; // radius bumi dalam meter
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}
<?php

namespace App\Services;

use App\Models\RoutePlan;
use App\Models\RouteWaypoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class RoutePlanningService
{
    // Fuel consumption: liter per km (default truk = 0.25 L/km)
    const FUEL_PER_KM = 0.25;
    const FUEL_PRICE  = 10000; // Rp per liter

    public function list(array $filters): LengthAwarePaginator
    {
        return RoutePlan::with([
                'jobOrder:id,job_number',
                'driver:id,name',
                'creator:id,name',
            ])
            ->when($filters['status']       ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['driver_id']    ?? null, fn($q, $v) => $q->where('driver_id', $v))
            ->when($filters['job_order_id'] ?? null, fn($q, $v) => $q->where('job_order_id', $v))
            ->when($filters['search']       ?? null, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('route_number', 'like', "%$v%")
                    ->orWhereHas('jobOrder', fn($jq) =>
                        $jq->where('job_number', 'like', "%$v%")
                    )
                )
            )
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, User $authUser): RoutePlan
    {
        return DB::transaction(function () use ($data, $authUser) {
            // Hitung estimasi jarak jika koordinat tersedia
            $distanceKm = null;
            if (isset($data['origin_lat'], $data['destination_lat'])) {
                $distanceKm = $this->haversineKm(
                    $data['origin_lat'], $data['origin_lng'],
                    $data['destination_lat'], $data['destination_lng']
                );
            }

            $fuelLiters  = $distanceKm ? round($distanceKm * self::FUEL_PER_KM, 2) : null;
            $fuelCost    = $fuelLiters ? $fuelLiters * self::FUEL_PRICE : null;
            $tollCost    = $data['estimated_toll_cost'] ?? 0;
            $totalCost   = ($fuelCost ?? 0) + $tollCost;
            $durationMin = $distanceKm ? (int)($distanceKm / 60 * 60) : null; // ~60 km/h

            $route = RoutePlan::create([
                ...$data,
                'route_number'               => RoutePlan::generateRouteNumber(),
                'created_by'                 => $authUser->id,
                'total_distance_km'          => $distanceKm ? round($distanceKm, 3) : null,
                'estimated_duration_minutes' => $durationMin,
                'estimated_fuel_liters'      => $fuelLiters,
                'estimated_fuel_cost'        => $fuelCost,
                'total_estimated_cost'       => $totalCost,
                'status'                     => 'draft',
            ]);

            // Simpan waypoints jika ada
            if (! empty($data['waypoints'])) {
                foreach ($data['waypoints'] as $i => $wp) {
                    RouteWaypoint::create([
                        'route_plan_id'          => $route->id,
                        'sequence'               => $i + 1,
                        'label'                  => $wp['label'] ?? null,
                        'address'                => $wp['address'],
                        'lat'                    => $wp['lat'],
                        'lng'                    => $wp['lng'],
                        'type'                   => $wp['type'] ?? 'other',
                        'estimated_stop_minutes' => $wp['estimated_stop_minutes'] ?? 0,
                        'estimated_arrival_at'   => $wp['estimated_arrival_at'] ?? null,
                    ]);
                }
            }

            return $route->load(['jobOrder', 'driver', 'creator', 'routeWaypoints']);
        });
    }

    public function updateStatus(RoutePlan $route, string $newStatus, User $authUser): RoutePlan
    {
        if (! $route->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from [{$route->status}] to [{$newStatus}]"
            );
        }

        $route->update(['status' => $newStatus]);

        // Kalau active, update job order status juga
        if ($newStatus === 'active') {
            $route->jobOrder->update(['status' => 'in_transit']);
        }

        if ($newStatus === 'completed') {
            $route->jobOrder->update(['status' => 'delivered', 'delivery_actual_at' => now()]);
        }

        return $route->fresh(['jobOrder', 'driver', 'creator', 'routeWaypoints']);
    }

    public function updateWaypoint(RouteWaypoint $waypoint, array $data): RouteWaypoint
    {
        $waypoint->update($data);

        // Kalau tiba di waypoint, catat waktu aktual
        if (($data['status'] ?? null) === 'arrived' && ! $waypoint->actual_arrival_at) {
            $waypoint->update(['actual_arrival_at' => now()]);
        }

        return $waypoint->fresh();
    }

    public function getOptimizedRoute(array $coordinates): array
    {
        // Nearest neighbor algorithm sederhana untuk optimasi urutan waypoint
        if (count($coordinates) <= 2) return $coordinates;

        $visited  = [0];
        $current  = 0;
        $remaining = array_keys(array_slice($coordinates, 1, null, true));

        while (! empty($remaining)) {
            $nearest     = null;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($remaining as $idx) {
                $d = $this->haversineKm(
                    $coordinates[$current]['lat'], $coordinates[$current]['lng'],
                    $coordinates[$idx]['lat'], $coordinates[$idx]['lng']
                );

                if ($d < $minDistance) {
                    $minDistance = $d;
                    $nearest     = $idx;
                }
            }

            $visited[]  = $nearest;
            $current    = $nearest;
            $remaining  = array_diff($remaining, [$nearest]);
        }

        return array_map(fn($i) => $coordinates[$i], $visited);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
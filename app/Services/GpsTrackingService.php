<?php

namespace App\Services;

use App\Models\DriverLocation;
use App\Models\Geofence;
use App\Models\GeofenceEvent;
use App\Models\TripTrack;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GpsTrackingService
{
    // Driver update lokasi
    public function updateLocation(array $data, User $driver): DriverLocation
    {
        return DB::transaction(function () use ($data, $driver) {
            // Simpan/update lokasi terkini
            $location = DriverLocation::updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'job_order_id' => $data['job_order_id'] ?? null,
                    'lat'          => $data['lat'],
                    'lng'          => $data['lng'],
                    'accuracy'     => $data['accuracy'] ?? null,
                    'speed_kmh'    => $data['speed_kmh'] ?? null,
                    'heading'      => $data['heading'] ?? null,
                    'battery_level'=> $data['battery_level'] ?? null,
                    'is_online'    => true,
                    'recorded_at'  => $data['recorded_at'] ?? now(),
                ]
            );

            // Simpan ke history trip kalau sedang ada job order
            if ($data['job_order_id'] ?? null) {
                $this->recordTripTrack($data, $driver->id);
            }

            // Cek geofence
            $this->checkGeofences($location, $driver);

            return $location->fresh(['driver:id,name', 'jobOrder:id,job_number']);
        });
    }

    // Admin: lihat semua driver aktif
    public function getActiveDrivers(): \Illuminate\Database\Eloquent\Collection
    {
        return DriverLocation::with(['driver:id,name,phone', 'jobOrder:id,job_number,status'])
            ->where('is_online', true)
            ->where('recorded_at', '>=', now()->subMinutes(15))
            ->get();
    }

    // Ambil history track job order
    public function getTripHistory(int $jobOrderId): array
    {
        $tracks = TripTrack::where('job_order_id', $jobOrderId)
            ->orderBy('recorded_at')
            ->get(['lat', 'lng', 'speed_kmh', 'heading', 'distance_from_prev_km', 'recorded_at']);

        $totalDistance = $tracks->sum('distance_from_prev_km');
        $avgSpeed      = $tracks->whereNotNull('speed_kmh')->avg('speed_kmh');

        return [
            'job_order_id'      => $jobOrderId,
            'total_distance_km' => round($totalDistance, 3),
            'avg_speed_kmh'     => round($avgSpeed ?? 0, 2),
            'total_points'      => $tracks->count(),
            'tracks'            => $tracks,
        ];
    }

    // Driver set offline
    public function setOffline(User $driver): void
    {
        DriverLocation::where('driver_id', $driver->id)
            ->update(['is_online' => false]);
    }

    // ── Private ────────────────────────────────────────────────────

    private function recordTripTrack(array $data, int $driverId): void
    {
        // Hitung jarak dari titik sebelumnya
        $prev = TripTrack::where('job_order_id', $data['job_order_id'])
            ->where('driver_id', $driverId)
            ->latest('recorded_at')
            ->first();

        $distanceFromPrev = null;
        if ($prev) {
            $distanceFromPrev = $this->haversineKm(
                $prev->lat, $prev->lng,
                $data['lat'], $data['lng']
            );
        }

        TripTrack::create([
            'job_order_id'          => $data['job_order_id'],
            'driver_id'             => $driverId,
            'lat'                   => $data['lat'],
            'lng'                   => $data['lng'],
            'speed_kmh'             => $data['speed_kmh'] ?? null,
            'heading'               => $data['heading'] ?? null,
            'distance_from_prev_km' => $distanceFromPrev,
            'recorded_at'           => $data['recorded_at'] ?? now(),
        ]);
    }

    private function checkGeofences(DriverLocation $location, User $driver): void
    {
        $geofences = Geofence::where('is_active', true)
            ->where('type', 'circle')
            ->get();

        foreach ($geofences as $geofence) {
            $isInside = $geofence->containsPoint($location->lat, $location->lng);

            // Cek event terakhir untuk driver ini di geofence ini
            $lastEvent = GeofenceEvent::where('geofence_id', $geofence->id)
                ->where('driver_id', $driver->id)
                ->latest('occurred_at')
                ->first();

            $wasInside = $lastEvent?->event_type === 'enter';

            if ($isInside && ! $wasInside && in_array($geofence->trigger, ['enter', 'both'])) {
                GeofenceEvent::create([
                    'geofence_id'  => $geofence->id,
                    'driver_id'    => $driver->id,
                    'job_order_id' => $location->job_order_id,
                    'event_type'   => 'enter',
                    'lat'          => $location->lat,
                    'lng'          => $location->lng,
                    'occurred_at'  => now(),
                ]);
            } elseif (! $isInside && $wasInside && in_array($geofence->trigger, ['exit', 'both'])) {
                GeofenceEvent::create([
                    'geofence_id'  => $geofence->id,
                    'driver_id'    => $driver->id,
                    'job_order_id' => $location->job_order_id,
                    'event_type'   => 'exit',
                    'lat'          => $location->lat,
                    'lng'          => $location->lng,
                    'occurred_at'  => now(),
                ]);
            }
        }
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
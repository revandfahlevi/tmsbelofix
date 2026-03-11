<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteOptimizationService
{
    private string $apiKey;
    private string $baseUrl = 'https://routes.googleapis.com';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key');
    }

    /**
     * Hitung jarak & durasi antara 2 titik via Google Routes API
     */
    public function calculateRoute(
        float $originLat, float $originLng,
        float $destLat,   float $destLng,
        array $waypoints = []
    ): array {
        // Jika API key belum diset, pakai kalkulasi Haversine lokal
        if (empty($this->apiKey) || $this->apiKey === 'your_google_maps_api_key_here') {
            return $this->calculateHaversine($originLat, $originLng, $destLat, $destLng, $waypoints);
        }

        try {
            $body = [
                'origin' => [
                    'location' => [
                        'latLng' => ['latitude' => $originLat, 'longitude' => $originLng]
                    ]
                ],
                'destination' => [
                    'location' => [
                        'latLng' => ['latitude' => $destLat, 'longitude' => $destLng]
                    ]
                ],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
                'computeAlternativeRoutes' => false,
                'routeModifiers' => [
                    'avoidTolls' => false,
                    'avoidHighways' => false,
                ],
                'languageCode' => 'id-ID',
                'units' => 'METRIC',
            ];

            // Tambah intermediate waypoints jika ada
            if (!empty($waypoints)) {
                $body['intermediates'] = array_map(fn($wp) => [
                    'location' => [
                        'latLng' => [
                            'latitude'  => $wp['lat'],
                            'longitude' => $wp['lng'],
                        ]
                    ]
                ], $waypoints);
            }

            $response = Http::withHeaders([
                'X-Goog-Api-Key'        => $this->apiKey,
                'X-Goog-FieldMask'      => 'routes.duration,routes.distanceMeters,routes.legs,routes.optimizedIntermediateWaypointIndex',
                'Content-Type'          => 'application/json',
            ])->post("{$this->baseUrl}/directions/v2:computeRoutes", $body);

            if ($response->failed()) {
                Log::error('Google Routes API error', ['response' => $response->json()]);
                return $this->calculateHaversine($originLat, $originLng, $destLat, $destLng, $waypoints);
            }

            $data   = $response->json();
            $route  = $data['routes'][0] ?? null;

            if (!$route) {
                return $this->calculateHaversine($originLat, $originLng, $destLat, $destLng, $waypoints);
            }

            $distanceMeters  = $route['distanceMeters'] ?? 0;
            $durationSeconds = (int) filter_var($route['duration'] ?? '0s', FILTER_SANITIZE_NUMBER_INT);

            return [
                'distance_km'     => round($distanceMeters / 1000, 2),
                'duration_hours'  => round($durationSeconds / 3600, 2),
                'duration_minutes'=> round($durationSeconds / 60),
                'source'          => 'google_maps',
                'optimized'       => true,
            ];

        } catch (\Exception $e) {
            Log::error('RouteOptimizationService error: ' . $e->getMessage());
            return $this->calculateHaversine($originLat, $originLng, $destLat, $destLng, $waypoints);
        }
    }

    /**
     * Optimasi urutan waypoints (Traveling Salesman Problem sederhana)
     * Pakai Google Routes API Waypoint Optimization
     */
    public function optimizeWaypoints(
        float $originLat, float $originLng,
        float $destLat,   float $destLng,
        array $waypoints = []
    ): array {
        if (empty($this->apiKey) || $this->apiKey === 'your_google_maps_api_key_here') {
            return $this->calculateRoute($originLat, $originLng, $destLat, $destLng, $waypoints);
        }

        try {
            $body = [
                'origin' => [
                    'location' => ['latLng' => ['latitude' => $originLat, 'longitude' => $originLng]]
                ],
                'destination' => [
                    'location' => ['latLng' => ['latitude' => $destLat, 'longitude' => $destLng]]
                ],
                'travelMode'    => 'DRIVE',
                'optimizeWaypointOrder' => true,  // ← kunci optimasi
            ];

            if (!empty($waypoints)) {
                $body['intermediates'] = array_map(fn($wp) => [
                    'location' => [
                        'latLng' => ['latitude' => $wp['lat'], 'longitude' => $wp['lng']]
                    ]
                ], $waypoints);
            }

            $response = Http::withHeaders([
                'X-Goog-Api-Key'   => $this->apiKey,
                'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.optimizedIntermediateWaypointIndex',
                'Content-Type'     => 'application/json',
            ])->post("{$this->baseUrl}/directions/v2:computeRoutes", $body);

            if ($response->failed()) {
                return $this->calculateRoute($originLat, $originLng, $destLat, $destLng, $waypoints);
            }

            $data  = $response->json();
            $route = $data['routes'][0] ?? null;

            if (!$route) {
                return $this->calculateRoute($originLat, $originLng, $destLat, $destLng, $waypoints);
            }

            $distanceMeters  = $route['distanceMeters'] ?? 0;
            $durationSeconds = (int) filter_var($route['duration'] ?? '0s', FILTER_SANITIZE_NUMBER_INT);

            return [
                'distance_km'             => round($distanceMeters / 1000, 2),
                'duration_hours'          => round($durationSeconds / 3600, 2),
                'duration_minutes'        => round($durationSeconds / 60),
                'optimized_waypoint_order'=> $route['optimizedIntermediateWaypointIndex'] ?? [],
                'source'                  => 'google_maps',
                'optimized'               => true,
            ];

        } catch (\Exception $e) {
            Log::error('Waypoint optimization error: ' . $e->getMessage());
            return $this->calculateRoute($originLat, $originLng, $destLat, $destLng, $waypoints);
        }
    }

    /**
     * Fallback: Haversine formula (kalkulasi jarak garis lurus)
     * Dipakai jika API key belum diset
     */
    private function calculateHaversine(
        float $originLat, float $originLng,
        float $destLat,   float $destLng,
        array $waypoints  = []
    ): array {
        $totalDistance = 0;

        $points = array_merge(
            [['lat' => $originLat, 'lng' => $originLng]],
            $waypoints,
            [['lat' => $destLat,   'lng' => $destLng]]
        );

        for ($i = 0; $i < count($points) - 1; $i++) {
            $totalDistance += $this->haversineDistance(
                $points[$i]['lat'],   $points[$i]['lng'],
                $points[$i+1]['lat'], $points[$i+1]['lng']
            );
        }

        // Estimasi kecepatan rata-rata 60 km/h untuk jalan darat Indonesia
        $durationHours = round($totalDistance / 60, 2);

        return [
            'distance_km'    => round($totalDistance, 2),
            'duration_hours' => $durationHours,
            'duration_minutes'=> round($durationHours * 60),
            'source'         => 'haversine',
            'optimized'      => false,
        ];
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371; // radius bumi km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                sin($dLng/2) * sin($dLng/2);
        $c    = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
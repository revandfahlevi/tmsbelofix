<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    /**
     * MOCK FUNGSI CALCULATE ROUTE
     * Nanti kalau API Key udah ada, tinggal ubah isi fungsi ini.
     */
    public function calculateRouteEstimate(array $origin, array $destination, array $waypoints = [])
    {
        // ---------------------------------------------------------
        // TODO: REAL IMPLEMENTATION (Kalau API Key udah ada)
        // ---------------------------------------------------------
        /*
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $response = Http::get("https://maps.googleapis.com/maps/api/directions/json", [
            'origin' => "{$origin['lat']},{$origin['lng']}",
            'destination' => "{$destination['lat']},{$destination['lng']}",
            // Format waypoints: optimize:true|lat,lng|lat,lng
            'waypoints' => $this->formatWaypoints($waypoints), 
            'key' => $apiKey
        ]);

        if ($response->successful() && $response['status'] === 'OK') {
            $route = $response['routes'][0]['legs'][0];
            return [
                'distance_km' => $route['distance']['value'] / 1000,
                'duration_hours' => round($route['duration']['value'] / 3600, 2),
            ];
        }
        */

        // ---------------------------------------------------------
        // MOCK IMPLEMENTATION (Dipakai sekarang sebelum ada API Key)
        // ---------------------------------------------------------
        Log::info('Mocking Google Maps API for route calculation...');
        
        // Pura-puranya ini hasil ngitung dari titik A ke titik B
        // Semakin banyak waypoints, kita tambahin simulasi jaraknya
        $baseDistance = 15.5; // km
        $baseDuration = 0.5;  // jam
        
        $totalDistance = $baseDistance + (count($waypoints) * 5.2);
        $totalDuration = $baseDuration + (count($waypoints) * 0.3);

        return [
            'distance_km'    => round($totalDistance, 2),
            'duration_hours' => round($totalDuration, 2),
            'status'         => 'OK_MOCK'
        ];
    }
}
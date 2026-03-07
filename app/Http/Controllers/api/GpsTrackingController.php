<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gps\UpdateLocationRequest;
use App\Models\Geofence;
use App\Services\GpsTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsTrackingController extends Controller
{
    public function __construct(private GpsTrackingService $service) {}

    // Driver: update lokasi (dipanggil tiap N detik dari app)
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $location = $this->service->updateLocation(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data'    => $location,
        ]);
    }

    // Driver: set offline
    public function setOffline(Request $request): JsonResponse
    {
        $this->service->setOffline($request->user());

        return response()->json(['success' => true, 'message' => 'Driver set offline.']);
    }

    // Admin: semua driver aktif
    public function activeDrivers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getActiveDrivers(),
        ]);
    }

    // Admin/Driver: history track job order
    public function tripHistory(int $jobOrderId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getTripHistory($jobOrderId),
        ]);
    }

    // Admin: CRUD geofence
    public function geofences(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Geofence::all(),
        ]);
    }

    public function storeGeofence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:circle,polygon',
            'center_lat'     => 'required_if:type,circle|numeric',
            'center_lng'     => 'required_if:type,circle|numeric',
            'radius_meters'  => 'required_if:type,circle|integer|min:1',
            'polygon_points' => 'required_if:type,polygon|array|min:3',
            'trigger'        => 'in:enter,exit,both',
            'is_active'      => 'boolean',
        ]);

        $geofence = Geofence::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Geofence created.',
            'data'    => $geofence,
        ], 201);
    }

    public function destroyGeofence(Geofence $geofence): JsonResponse
    {
        $geofence->delete();

        return response()->json(['success' => true, 'message' => 'Geofence deleted.']);
    }
}
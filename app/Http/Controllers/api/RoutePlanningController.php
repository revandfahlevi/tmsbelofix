<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Route\CreateRoutePlanRequest;
use App\Http\Requests\Route\UpdateRouteStatusRequest;
use App\Models\RoutePlan;
use App\Models\RouteWaypoint;
use App\Services\RoutePlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Services\GoogleMapsService;

class RoutePlanningController extends Controller
{
    public function __construct(private RoutePlanningService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->list($request->all()),
        ]);
    }

    public function store(CreateRoutePlanRequest $request): JsonResponse
    {
        $route = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Route plan created.',
            'data'    => $route,
        ], 201);
    }

    public function update(Request $request, RoutePlan $routePlan): JsonResponse
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'job_order_id'             => 'nullable|integer',
            'total_distance_km'        => 'nullable|numeric',
            'estimated_duration_hours' => 'nullable|numeric',
            'origin_address'           => 'required|string',
            'origin_lat'               => 'required|numeric',
            'origin_lng'               => 'required|numeric',
            'destination_address'      => 'required|string',
            'destination_lat'          => 'required|numeric',
            'destination_lng'          => 'required|numeric',
            'waypoints'                => 'nullable|array',
            'waypoints.*.address'      => 'required|string',
            'waypoints.*.lat'          => 'required|numeric',
            'waypoints.*.lng'          => 'required|numeric',
            'waypoints.*.sequence'     => 'required|integer',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        
        try {
            // 2. Update tabel utama (route_plans)
            $routePlan->update([
                'name'                     => $validated['name'],
                'job_order_id'             => $validated['job_order_id'],
                'total_distance_km'        => $validated['total_distance_km'],
                'estimated_duration_hours' => $validated['estimated_duration_hours'],
                'origin_address'           => $validated['origin_address'],
                'origin_lat'               => $validated['origin_lat'],
                'origin_lng'               => $validated['origin_lng'],
                'destination_address'      => $validated['destination_address'],
                'destination_lat'          => $validated['destination_lat'],
                'destination_lng'          => $validated['destination_lng'],
            ]);

            // 3. Update tabel anak (route_waypoints)
            if (isset($validated['waypoints'])) {
                // Hapus waypoints lama biar bersih
                $routePlan->routeWaypoints()->delete();
                
                // Insert waypoints baru
                $routePlan->routeWaypoints()->createMany($validated['waypoints']);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route plan updated successfully.',
                'data'    => $routePlan->load('routeWaypoints'),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update route: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(RoutePlan $routePlan): JsonResponse
    {
        $routePlan->load(['jobOrder', 'driver:id,name,phone', 'creator:id,name', 'routeWaypoints']);

        return response()->json(['success' => true, 'data' => $routePlan]);
    }

    public function updateStatus(UpdateRouteStatusRequest $request, RoutePlan $routePlan): JsonResponse
    {
        try {
            $route = $this->service->updateStatus(
                $routePlan,
                $request->validated('status'),
                $request->user()
            );

            return response()->json(['success' => true, 'message' => 'Status updated.', 'data' => $route]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateWaypoint(Request $request, RoutePlan $routePlan, RouteWaypoint $waypoint): JsonResponse
    {
        $validated = $request->validate([
            'status'            => 'sometimes|in:pending,arrived,departed,skipped',
            'actual_arrival_at' => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $waypoint = $this->service->updateWaypoint($waypoint, $validated);

        return response()->json(['success' => true, 'data' => $waypoint]);
    }

    public function optimizeRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coordinates'         => 'required|array|min:2',
            'coordinates.*.lat'   => 'required|numeric',
            'coordinates.*.lng'   => 'required|numeric',
            'coordinates.*.label' => 'nullable|string',
        ]);

        $optimized = $this->service->getOptimizedRoute($validated['coordinates']);

        return response()->json(['success' => true, 'data' => $optimized]);
    }

    public function destroy(RoutePlan $routePlan): JsonResponse
    {
        if (! in_array($routePlan->status, ['draft', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or cancelled routes can be deleted.',
            ], 422);
        }

        $routePlan->delete();

        return response()->json(['success' => true, 'message' => 'Route plan deleted.']);
    }

    // 👇 INI DIA FUNGSI BARUNYA UDAH MASUK KE DALAM CLASS 👇
    public function getEstimateFromJobOrder($jobOrderId, GoogleMapsService $mapsService)
    {
        // 1. Ambil data Job Order beserta titik-titiknya (asumsi lu nyimpen origin/dest di Job Order)
        $jobOrder = JobOrder::with('waypoints')->findOrFail($jobOrderId);

        // Asumsi di tabel Job Order ada origin_lat, origin_lng, destination_lat, destination_lng
        $origin = ['lat' => $jobOrder->origin_lat, 'lng' => $jobOrder->origin_lng];
        $destination = ['lat' => $jobOrder->destination_lat, 'lng' => $jobOrder->destination_lng];
        
        // Ambil waypoints dari Job Order kalau ada
        $waypoints = $jobOrder->waypoints->map(function($wp) {
            return ['lat' => $wp->lat, 'lng' => $wp->lng];
        })->toArray();

        // 2. Lempar ke Google Maps Mock Service
        $estimate = $mapsService->calculateRouteEstimate($origin, $destination, $waypoints);

        return response()->json([
            'success' => true,
            'data' => [
                'job_order_id' => $jobOrder->id,
                'origin' => $origin,
                'destination' => $destination,
                'waypoints' => $waypoints,
                'distance_km' => $estimate['distance_km'],
                'duration_hours' => $estimate['duration_hours']
            ]
        ]);
    }
}
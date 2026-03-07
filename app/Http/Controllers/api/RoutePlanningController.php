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
}
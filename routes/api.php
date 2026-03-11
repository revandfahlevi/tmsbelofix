<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\CarrierController;
use App\Http\Controllers\Api\JobOrderController;
use App\Http\Controllers\Api\GpsTrackingController;
use App\Http\Controllers\Api\RoutePlanningController;
use App\Http\Controllers\Api\Driver\DriverJobController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CostEstimationController;
use App\Http\Controllers\Api\ProofOfDeliveryController;

// ================================================================
// PUBLIC — Tidak butuh auth
// ================================================================
Route::prefix('auth')->group(function () {
    Route::post('login',   [AuthController::class, 'login'])->name('login');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
});

// ================================================================
// PROTECTED — Semua butuh auth:sanctum
// ================================================================
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth Self-Service ────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('me',                 [AuthController::class, 'me']);
        Route::post('logout',            [AuthController::class, 'logout']);
        Route::post('logout-all',        [AuthController::class, 'logoutAll']);
        Route::put('change-password',    [AuthController::class, 'changePassword']);
        Route::put('update-fcm-token',   [AuthController::class, 'updateFcmToken']);
    });

    // ── Job Orders ───────────────────────────────────────────────
    Route::apiResource('job-orders', JobOrderController::class);
    Route::patch('job-orders/{jobOrder}/status',       [JobOrderController::class, 'updateStatus']);
    Route::post('job-orders/{jobOrder}/assign-driver', [JobOrderController::class, 'assignDriver'])
        ->middleware('role:admin');

    // ── Route Planning ───────────────────────────────────────────
    Route::get('route-plans',                                    [RoutePlanningController::class, 'index']);
    Route::post('route-plans',                                   [RoutePlanningController::class, 'store']);
    Route::post('route-plans/optimize',                          [RoutePlanningController::class, 'optimizeRoute']);
    Route::get('route-plans/estimate/{jobOrderId}',              [RoutePlanningController::class, 'getEstimateFromJobOrder']);
    Route::get('route-plans/{routePlan}',                        [RoutePlanningController::class, 'show']);
    Route::put('route-plans/{routePlan}',                        [RoutePlanningController::class, 'update']);
    Route::delete('route-plans/{routePlan}',                     [RoutePlanningController::class, 'destroy']);
    Route::patch('route-plans/{routePlan}/status',               [RoutePlanningController::class, 'updateStatus']);
    Route::patch('route-plans/{routePlan}/waypoints/{waypoint}', [RoutePlanningController::class, 'updateWaypoint']);

    // ── GPS — Driver ─────────────────────────────────────────────
    Route::post('gps/location', [GpsTrackingController::class, 'updateLocation'])->middleware('role:driver');
    Route::post('gps/offline',  [GpsTrackingController::class, 'setOffline'])->middleware('role:driver');

    // ── GPS — Admin ──────────────────────────────────────────────
    Route::get('gps/active-drivers',          [GpsTrackingController::class, 'activeDrivers'])->middleware('role:admin');
    Route::get('gps/geofences',               [GpsTrackingController::class, 'geofences'])->middleware('role:admin');
    Route::post('gps/geofences',              [GpsTrackingController::class, 'storeGeofence'])->middleware('role:admin');
    Route::delete('gps/geofences/{geofence}', [GpsTrackingController::class, 'destroyGeofence'])->middleware('role:admin');

    // ── GPS — Shared ─────────────────────────────────────────────
    Route::get('gps/trip-history/{jobOrderId}', [GpsTrackingController::class, 'tripHistory']);

    // ── POD ──────────────────────────────────────────────────────
    Route::get('pods',                               [ProofOfDeliveryController::class, 'index']);
    Route::get('pods/job-order/{jobOrderId}',        [ProofOfDeliveryController::class, 'getByJobOrder']);
    Route::get('pods/{proofOfDelivery}',             [ProofOfDeliveryController::class, 'show']);
    Route::post('pods',                              [ProofOfDeliveryController::class, 'store']);
    Route::patch('pods/{proofOfDelivery}/verify',    [ProofOfDeliveryController::class, 'verify'])->middleware('role:admin');
    Route::patch('pods/{proofOfDelivery}/reject',    [ProofOfDeliveryController::class, 'reject'])->middleware('role:admin');

    // ── Dispatch ─────────────────────────────────────────────────
    Route::get('dispatch',                     [DispatchController::class, 'index']);
    Route::get('dispatch/{dispatch}',          [DispatchController::class, 'show']);
    Route::post('dispatch',                    [DispatchController::class, 'store'])->middleware('role:admin');
    Route::patch('dispatch/{dispatch}/status', [DispatchController::class, 'updateStatus']);
    Route::delete('dispatch/{dispatch}',       [DispatchController::class, 'destroy'])->middleware('role:admin');

    // ── Driver My-Jobs ────────────────────────────────────────────
    Route::prefix('driver')->middleware('role:driver')->group(function () {
        Route::get('my-jobs',                            [DriverJobController::class, 'index']);
        Route::get('my-jobs/{jobOrder}',                 [DriverJobController::class, 'show']);
        Route::post('my-jobs/{jobOrder}/apply',          [DriverJobController::class, 'apply']);
        Route::delete('my-jobs/{jobOrder}/cancel-apply', [DriverJobController::class, 'cancelApply']);
        Route::patch('my-jobs/{jobOrder}/update-status', [DriverJobController::class, 'updateStatus']);
        Route::patch('status', function (Request $request) {
        $request->validate(['availability_status' => 'required|string']);
        $user = $request->user();
        if ($user->driverProfile) {
            $user->driverProfile->update([
                'availability_status' => $request->availability_status
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Status updated']);
    });
    Route::patch('status', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'availability_status' => 'required|in:available,on_duty,rest,off_duty'
    ]);

    $user = $request->user();

    // Update atau create driver_profile
    $user->driverProfile()->updateOrCreate(
        ['user_id' => $user->id],
        ['availability_status' => $request->availability_status]
    );

    return response()->json([
        'success' => true,
        'message' => 'Status berhasil diperbarui',
        'data'    => ['availability_status' => $request->availability_status]
    ]);
});
    });

    // ── Analytics ─────────────────────────────────────────────────
    Route::get('/analytics/utilization', function (Request $request) {
        $now   = now();
        $start = $request->start ? \Carbon\Carbon::parse($request->start) : $now->copy()->subDays(30);
        $end   = $request->end   ? \Carbon\Carbon::parse($request->end)   : $now;

        $jobs = \App\Models\JobOrder::whereBetween('created_at', [$start, $end]);

        $totalJobs     = (clone $jobs)->count();
        $completedJobs = (clone $jobs)->where('status', 'completed')->count();
        $cancelledJobs = (clone $jobs)->where('status', 'cancelled')->count();
        $activeJobs    = (clone $jobs)->whereIn('status', ['assigned','in_progress','picked_up','in_transit'])->count();
       $totalRevenue = (clone $jobs)->where('status', 'completed')
    ->selectRaw('SUM(COALESCE(actual_cost, estimated_cost, 0)) as total')
    ->value('total') ?? 0;
        $estimatedRev  = (clone $jobs)->sum('estimated_cost');

        $revenueChart = \App\Models\JobOrder::where('status', 'completed')
            ->whereBetween('delivery_actual_at', [$start, $end])
            ->selectRaw('DATE(delivery_actual_at) as date, COUNT(*) as count, SUM(COALESCE(actual_cost, estimated_cost, 0)) as revenue')
            ->groupBy('date')->orderBy('date')->get();

        $statusChart = \App\Models\JobOrder::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->get();

        $driverStats = \App\Models\JobOrder::whereBetween('created_at', [$start, $end])
            ->whereNotNull('assigned_driver_id')
            ->with('driver:id,name')
            ->selectRaw('assigned_driver_id, COUNT(*) as total_trips,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(actual_cost) as revenue')
            ->groupBy('assigned_driver_id')->get();

        $vehicleStats = \App\Models\JobOrder::whereBetween('created_at', [$start, $end])
            ->whereNotNull('assigned_vehicle_id')
            ->with('vehicle:id,plate_number,vehicle_type')
            ->selectRaw('assigned_vehicle_id, COUNT(*) as total_trips,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(cargo_weight_kg) as total_weight')
            ->groupBy('assigned_vehicle_id')->get();

        $cargoChart = \App\Models\JobOrder::whereBetween('created_at', [$start, $end])
            ->selectRaw('cargo_type, COUNT(*) as count, SUM(cargo_weight_kg) as total_weight')
            ->groupBy('cargo_type')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period'        => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'summary'       => compact('totalJobs','completedJobs','cancelledJobs','activeJobs','totalRevenue','estimatedRev'),
                'revenue_chart' => $revenueChart,
                'status_chart'  => $statusChart,
                'driver_stats'  => $driverStats,
                'vehicle_stats' => $vehicleStats,
                'cargo_chart'   => $cargoChart,
            ]
        ]);
    });

    // ── Dashboard Revenue Chart ───────────────────────────────────
   Route::get('/dashboard/revenue', function (Request $request) {
    $now = now();

    // Ambil semua completed job, pakai actual_cost atau fallback ke estimated_cost
    $totalRevenue = \App\Models\JobOrder::where('status', 'completed')
        ->selectRaw('SUM(COALESCE(actual_cost, estimated_cost, 0)) as total')
        ->value('total') ?? 0;

    $revenueChart = \App\Models\JobOrder::where('status', 'completed')
        ->where('updated_at', '>=', $now->copy()->subDays(30))
        ->selectRaw('DATE(updated_at) as date, SUM(COALESCE(actual_cost, estimated_cost, 0)) as revenue')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $lastMonthRevenue = \App\Models\JobOrder::where('status', 'completed')
        ->whereBetween('updated_at', [
            $now->copy()->subMonth()->startOfMonth(),
            $now->copy()->subMonth()->endOfMonth()
        ])
        ->selectRaw('SUM(COALESCE(actual_cost, estimated_cost, 0)) as total')
        ->value('total') ?? 0;

    $percentageChange = $lastMonthRevenue > 0
        ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
        : 0;

    return response()->json([
        'total_revenue'     => $totalRevenue,
        'chart_data'        => $revenueChart,
        'percentage_change' => $percentageChange,
    ]);
});

    // ── Admin ─────────────────────────────────────────────────────
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // User management
        Route::post('register',                 [AuthController::class, 'register']);
        Route::apiResource('users',             UserManagementController::class);
        Route::patch('users/{user}/status',     [UserManagementController::class, 'updateStatus']);
        Route::get('users/{user}/auth-logs',    [UserManagementController::class, 'authLogs']);

        // Carrier & Vehicles
        Route::apiResource('carriers', CarrierController::class);
        Route::post('carriers/{carrier}/vehicles',              [CarrierController::class, 'storeVehicle']);
        Route::put('carriers/{carrier}/vehicles/{vehicle}',     [CarrierController::class, 'updateVehicle']);
        Route::delete('carriers/{carrier}/vehicles/{vehicle}',  [CarrierController::class, 'destroyVehicle']);
        Route::get('vehicles',                                  [CarrierController::class, 'availableVehicles']);

        // Carrier Assignments
        Route::get('carrier-assignments',                       [CarrierController::class, 'assignments']);
        Route::post('carrier-assignments',                      [CarrierController::class, 'createAssignment']);
        Route::get('carrier-assignments/{assignment}',          [CarrierController::class, 'showAssignment']);
        Route::patch('carrier-assignments/{assignment}/status', [CarrierController::class, 'updateAssignmentStatus']);
        Route::post('carrier-assignments/{assignment}/send-spk',[CarrierController::class, 'sendSpk']);
    });
});
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobOrderController;
use App\Http\Controllers\Api\Admin\CarrierController;

use App\Http\Controllers\Api\GpsTrackingController;

use App\Http\Controllers\Api\RoutePlanningController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('route-plans', [RoutePlanningController::class, 'index']);
    Route::post('route-plans', [RoutePlanningController::class, 'store']);
    // Tambahin di dalam grup auth:sanctum ya
Route::get('route-plans/estimate/{jobOrderId}', [RoutePlanningController::class, 'getEstimateFromJobOrder']);
    Route::put('route-plans/{routePlan}', [RoutePlanningController::class, 'update']);
    Route::get('route-plans/{routePlan}', [RoutePlanningController::class, 'show']);
    Route::delete('route-plans/{routePlan}', [RoutePlanningController::class, 'destroy']);
    Route::patch('route-plans/{routePlan}/status', [RoutePlanningController::class, 'updateStatus']);
    Route::patch('route-plans/{routePlan}/waypoints/{waypoint}', [RoutePlanningController::class, 'updateWaypoint']);
    Route::post('route-plans/optimize', [RoutePlanningController::class, 'optimizeRoute']);
});

// GPS - Driver (update lokasi dari mobile app)
Route::middleware(['auth:sanctum', 'role:driver'])->group(function () {
    Route::post('gps/location', [GpsTrackingController::class, 'updateLocation']);
    Route::post('gps/offline', [GpsTrackingController::class, 'setOffline']);
});

// GPS - Admin (monitoring)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('gps/active-drivers', [GpsTrackingController::class, 'activeDrivers']);
    Route::get('gps/geofences', [GpsTrackingController::class, 'geofences']);
    Route::post('gps/geofences', [GpsTrackingController::class, 'storeGeofence']);
    Route::delete('gps/geofences/{geofence}', [GpsTrackingController::class, 'destroyGeofence']);
});

// GPS - Shared (admin & driver)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('gps/trip-history/{jobOrderId}', [GpsTrackingController::class, 'tripHistory']);
});

// ── Job Orders ──────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('job-orders', JobOrderController::class);

    // Status update (all roles)
    Route::patch('job-orders/{jobOrder}/status', [JobOrderController::class, 'updateStatus']);

    // Assign driver (admin only)
    Route::post('job-orders/{jobOrder}/assign-driver', [JobOrderController::class, 'assignDriver'])
        ->middleware('role:admin');
});

Route::prefix('auth')->group(function () {
    // ✅ Tambahkan ->name() agar middleware tidak error
    Route::post('/login',   [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
});

// ========================================
// PROTECTED (Auth Required)
// ========================================
Route::middleware('auth:sanctum')->group(function () {

    // Self-service auth
    Route::prefix('auth')->group(function () {
        Route::get('/me',                [AuthController::class, 'me']);
        Route::post('/logout',           [AuthController::class, 'logout']);
        Route::post('/logout-all',       [AuthController::class, 'logoutAll']);
        Route::put('/change-password',   [AuthController::class, 'changePassword']);
        Route::put('/update-fcm-token',  [AuthController::class, 'updateFcmToken']);
    });

    // ===== ADMIN ONLY =====
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/register',                        [AuthController::class, 'register']);
        Route::apiResource('/users',                    UserManagementController::class);
        Route::patch('/users/{user}/status',            [UserManagementController::class, 'updateStatus']);
        Route::get('/users/{user}/auth-logs',           [UserManagementController::class, 'authLogs']);

        // TODO next sprint:
        // Route::apiResource('/job-orders',            JobOrderController::class);
        // Route::apiResource('/carrier-assignments',   CarrierAssignmentController::class);
        // Route::apiResource('/dispatches',            DispatchController::class);
        // Route::apiResource('/invoices',              InvoiceController::class);
        // Route::apiResource('/routes',                RoutePlanningController::class);
    });

    // ===== DRIVER ONLY =====
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        // TODO:
        // Route::post('/job-orders/{jobOrder}/apply',           [DriverJobController::class, 'apply']);
        // Route::patch('/dispatches/{dispatch}/status',         [DriverDispatchController::class, 'updateStatus']);
        // Route::post('/gps/update',                            [GpsController::class, 'updateLocation']);
        // Route::post('/dispatches/{dispatch}/pod',             [PodController::class, 'capture']);
        // Route::get('/my-jobs',                                [DriverJobController::class, 'myJobs']);
    });

    // ===== USER/EMPLOYEE (read-only monitoring) =====
    Route::middleware('role:user,admin')->prefix('monitoring')->group(function () {
        // TODO:
        // Route::get('/job-orders',    [MonitoringController::class, 'jobOrders']);
        // Route::get('/dispatches',    [MonitoringController::class, 'dispatches']);
        // Route::get('/gps/drivers',   [GpsController::class, 'allDriverLocations']);
    });
});
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Master Carrier
    Route::apiResource('carriers', CarrierController::class);

    // Vehicles per carrier
    Route::post('carriers/{carrier}/vehicles', [CarrierController::class, 'storeVehicle']);
    Route::put('carriers/{carrier}/vehicles/{vehicle}', [CarrierController::class, 'updateVehicle']);

    // Assignments
    Route::get('carrier-assignments', [CarrierController::class, 'assignments']);
    Route::post('carrier-assignments', [CarrierController::class, 'createAssignment']);
    Route::get('carrier-assignments/{assignment}', [CarrierController::class, 'showAssignment']);
    Route::patch('carrier-assignments/{assignment}/status', [CarrierController::class, 'updateAssignmentStatus']);
    Route::post('carrier-assignments/{assignment}/send-spk', [CarrierController::class, 'sendSpk']);
});
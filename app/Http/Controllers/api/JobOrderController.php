<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobOrder\CreateJobOrderRequest;
use App\Http\Requests\JobOrder\UpdateJobOrderRequest;
use App\Http\Requests\JobOrder\UpdateStatusRequest;
use App\Http\Requests\JobOrder\AssignDriverRequest;
use App\Services\JobOrderService;
use App\Services\FcmService;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobOrderController extends Controller
{
    public function __construct(
        private JobOrderService $service,
        private FcmService $fcm,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->list($request->all(), $request->user());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(CreateJobOrderRequest $request): JsonResponse
    {
        $jo = $this->service->create($request->validated(), $request->user());
        return response()->json(['success' => true, 'message' => 'Job order created.', 'data' => $jo], 201);
    }

    public function show(JobOrder $jobOrder): JsonResponse
    {
        $jobOrder->load(['creator:id,name', 'driver:id,name,phone', 'admin:id,name', 'statusLogs.changedBy:id,name']);
        return response()->json(['success' => true, 'data' => $jobOrder]);
    }

    public function update(UpdateJobOrderRequest $request, JobOrder $jobOrder): JsonResponse
    {
        $jo = $this->service->update($jobOrder, $request->validated(), $request->user());
        return response()->json(['success' => true, 'message' => 'Job order updated.', 'data' => $jo]);
    }

    public function updateStatus(UpdateStatusRequest $request, JobOrder $jobOrder): JsonResponse
    {
        try {
            $jo = $this->service->updateStatus(
                $jobOrder,
                $request->validated('status'),
                $request->validated(),
                $request->user()
            );
            return response()->json(['success' => true, 'message' => 'Status updated.', 'data' => $jo]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Admin assign driver + kendaraan ───────────────────────────────
    // Status tetap 'pending', driver_acceptance = 'waiting'
    // Driver dapat notif → harus terima/tolak dulu
    public function assignDriver(AssignDriverRequest $request, JobOrder $jobOrder): JsonResponse
    {
        try {
            $jo = $this->service->assignDriver(
                $jobOrder,
                $request->validated('driver_id'),
                $request->user()
            );

            // Set waiting — belum assigned sampai driver confirm
            $jo->update([
                'driver_acceptance'   => 'waiting',
                'driver_responded_at' => null,
                'status'              => 'pending', // tetap pending sampai driver terima
            ]);

            // Kirim notif ke driver
            $driver = User::find($request->validated('driver_id'));
            if ($driver?->fcm_token) {
                $this->fcm->sendToDevice(
                    $driver->fcm_token,
                    '📦 Ada Job Order Untukmu!',
                    "Job {$jo->job_number}: {$jo->origin_city} → {$jo->destination_city}. Silakan terima atau tolak.",
                    [
                        'type'       => 'job_offered',
                        'job_id'     => (string) $jo->id,
                        'job_number' => $jo->job_number,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Driver ditugaskan, menunggu konfirmasi driver.',
                'data'    => $jo->fresh(['driver']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 404);
        }
    }

    // ── Driver terima job ─────────────────────────────────────────────
    public function driverAccept(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $user = $request->user();

        if ($jobOrder->assigned_driver_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Bukan job order Anda'], 403);
        }
        if ($jobOrder->driver_acceptance !== 'waiting') {
            return response()->json(['success' => false, 'message' => 'Job tidak dalam status menunggu konfirmasi'], 422);
        }

        $jobOrder->update([
            'status'              => 'assigned',
            'driver_acceptance'   => 'accepted',
            'driver_responded_at' => now(),
        ]);

        // Notif admin
        $adminTokens = User::where('role', 'admin')
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (!empty($adminTokens)) {
            $this->fcm->sendToMultiple(
                $adminTokens,
                '✅ Driver Terima Job',
                "{$user->name} menerima job {$jobOrder->job_number}. Siap berangkat!",
                ['type' => 'job_accepted', 'job_number' => $jobOrder->job_number]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Job order diterima! Silakan mulai pengiriman.',
            'data'    => $jobOrder->fresh(),
        ]);
    }

    // ── Driver tolak job ──────────────────────────────────────────────
    public function driverReject(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $user = $request->user();

        if ($jobOrder->assigned_driver_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Bukan job order Anda'], 403);
        }
        if ($jobOrder->driver_acceptance !== 'waiting') {
            return response()->json(['success' => false, 'message' => 'Job tidak dalam status menunggu konfirmasi'], 422);
        }

        $jobOrder->update([
            'status'                  => 'pending',
            'driver_acceptance'       => 'rejected',
            'driver_rejection_reason' => $request->reason,
            'driver_responded_at'     => now(),
            'assigned_driver_id'      => null,
            'assigned_vehicle_id'     => null,
        ]);

        // Notif admin — driver tolak, pilih driver lain
        $adminTokens = User::where('role', 'admin')
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (!empty($adminTokens)) {
            $this->fcm->sendToMultiple(
                $adminTokens,
                '❌ Driver Tolak Job',
                "{$user->name} menolak job {$jobOrder->job_number}. Silakan pilih driver lain.",
                ['type' => 'job_rejected', 'job_number' => $jobOrder->job_number]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Job order ditolak.',
            'data'    => $jobOrder->fresh(),
        ]);
    }

    public function destroy(Request $request, JobOrder $jobOrder): JsonResponse
    {
        try {
            $this->service->delete($jobOrder, $request->user());
            return response()->json(['success' => true, 'message' => 'Job order deleted.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
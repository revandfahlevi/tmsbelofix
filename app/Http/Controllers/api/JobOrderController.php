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

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(CreateJobOrderRequest $request): JsonResponse
    {
        $jo = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Job order created.',
            'data'    => $jo,
        ], 201);
    }

    public function show(JobOrder $jobOrder): JsonResponse
    {
        $jobOrder->load([
            'creator:id,name',
            'driver:id,name,phone',
            'admin:id,name',
            'statusLogs.changedBy:id,name',
        ]);

        return response()->json(['success' => true, 'data' => $jobOrder]);
    }

    public function update(UpdateJobOrderRequest $request, JobOrder $jobOrder): JsonResponse
    {
        $jo = $this->service->update($jobOrder, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Job order updated.',
            'data'    => $jo,
        ]);
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

            return response()->json([
                'success' => true,
                'message' => 'Status updated.',
                'data'    => $jo,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function assignDriver(AssignDriverRequest $request, JobOrder $jobOrder): JsonResponse
    {
        try {
            $jo = $this->service->assignDriver(
                $jobOrder,
                $request->validated('driver_id'),
                $request->user()
            );

            // ── Kirim notif ke driver ──────────────────────
            $driver = User::find($request->validated('driver_id'));
            if ($driver?->fcm_token) {
                $this->fcm->notifyDriverAssigned(
                    $driver->fcm_token,
                    $jobOrder->job_number,
                    $jobOrder->origin_city,
                    $jobOrder->destination_city
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Driver assigned.',
                'data'    => $jo,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found.',
            ], 404);
        }
    }

    // Driver apply/mengajukan diri ke job order
    public function driverApply(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        if ($jobOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Job order tidak dalam status pending',
            ], 422);
        }

        $jobOrder->update([
            'applicant_driver_id' => $user->id,
            'driver_notes'        => $request->notes,
        ]);

        // ── Kirim notif ke semua admin ─────────────────────
        $adminTokens = User::where('role', 'admin')
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (!empty($adminTokens)) {
            $this->fcm->notifyAdminDriverApplied(
                $adminTokens,
                $user->name,
                $jobOrder->job_number
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengajukan diri',
            'data'    => $jobOrder->fresh(),
        ]);
    }

    public function destroy(Request $request, JobOrder $jobOrder): JsonResponse
    {
        try {
            $this->service->delete($jobOrder, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Job order deleted.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
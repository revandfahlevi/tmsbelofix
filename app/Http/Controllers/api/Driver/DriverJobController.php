<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverJobController extends Controller
{
    // GET /api/driver/my-jobs?filter=active|available|applied|history
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();
        $filter = $request->filter ?? 'active';

        $query = JobOrder::with(['creator:id,name', 'driver:id,name,phone'])
            ->orderBy('created_at', 'desc');

        switch ($filter) {
            // Job yang sedang dikerjakan driver ini
            case 'active':
                $query->where('assigned_driver_id', $driver->id)
                    ->whereIn('status', [
                        'assigned', 'in_progress', 'picked_up', 'in_transit'
                    ]);
                break;

            // Job pending yang belum ada driver (bisa di-apply)
            case 'available':
                $query->whereNull('assigned_driver_id')
                    ->where('status', 'pending');
                break;

            // Job yang sudah di-apply driver ini tapi belum assigned
            case 'applied':
                $query->where('applicant_driver_id', $driver->id)
                    ->where('status', 'pending')
                    ->whereNotNull('applicant_driver_id');
                break;

            // Riwayat selesai
            case 'history':
                $query->where('assigned_driver_id', $driver->id)
                    ->whereIn('status', ['delivered', 'completed', 'cancelled']);
                break;

            default:
                $query->where('assigned_driver_id', $driver->id);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 20),
        ]);
    }

    // GET /api/driver/my-jobs/{jobOrder}
    public function show(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $driver = $request->user();

        // Cek akses — hanya job milik driver ini atau job available
        if (
            $jobOrder->assigned_driver_id !== $driver->id &&
            $jobOrder->applicant_driver_id !== $driver->id &&
            !($jobOrder->status === 'pending' && is_null($jobOrder->assigned_driver_id))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak',
            ], 403);
        }

        $jobOrder->load(['creator:id,name', 'driver:id,name,phone']);

        return response()->json(['success' => true, 'data' => $jobOrder]);
    }

    // POST /api/driver/my-jobs/{jobOrder}/apply
    public function apply(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $driver = $request->user();

        if ($jobOrder->status !== 'pending' || !is_null($jobOrder->assigned_driver_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Job ini tidak tersedia untuk di-apply',
            ], 422);
        }

        if ($jobOrder->applicant_driver_id === $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah apply job ini',
            ], 422);
        }

        $jobOrder->update([
            'applicant_driver_id' => $driver->id,
            'driver_notes'        => $request->driver_notes ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil apply job. Menunggu persetujuan admin.',
            'data'    => $jobOrder->fresh(),
        ]);
    }

    // DELETE /api/driver/my-jobs/{jobOrder}/cancel-apply
    public function cancelApply(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $driver = $request->user();

        if ($jobOrder->applicant_driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak punya apply pada job ini',
            ], 403);
        }

        $jobOrder->update([
            'applicant_driver_id' => null,
            'driver_notes'        => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Apply dibatalkan',
        ]);
    }

    // PATCH /api/driver/my-jobs/{jobOrder}/update-status
    public function updateStatus(Request $request, JobOrder $jobOrder): JsonResponse
    {
        $driver = $request->user();

        if ($jobOrder->assigned_driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job ini bukan milik Anda',
            ], 403);
        }

        $request->validate(['status' => 'required|string']);

        $allowed = [
            'assigned'    => ['in_progress'],
            'in_progress' => ['picked_up'],
            'picked_up'   => ['in_transit'],
            'in_transit'  => ['delivered'],
        ];

        $next = $request->status;
        if (!in_array($next, $allowed[$jobOrder->status] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => "Tidak bisa update dari [{$jobOrder->status}] ke [{$next}]",
            ], 422);
        }

        $jobOrder->update([
            'status'             => $next,
            'delivery_actual_at' => $next === 'delivered' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status diperbarui',
            'data'    => $jobOrder->fresh(['driver:id,name']),
        ]);
    }
}
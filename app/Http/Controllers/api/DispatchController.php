<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\DispatchStatusLog;
use App\Models\JobOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DispatchController extends Controller
{
    // GET /api/v1/dispatch
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Dispatch::with(['driver:id,name,phone', 'jobOrder:id,job_number,origin_address,destination_address', 'dispatcher:id,name']);

        // Driver hanya lihat dispatch milik sendiri
        if ($user->isDriver()) {
            $query->where('driver_id', $user->id);
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date'))   $query->whereDate('scheduled_departure', $request->date);

        return response()->json([
            'success' => true,
            'data'    => $query->latest()->paginate($request->input('per_page', 15)),
        ]);
    }

    // GET /api/v1/dispatch/{id}
    public function show(Dispatch $dispatch): JsonResponse
    {
        $dispatch->load([
            'driver:id,name,phone', 'dispatcher:id,name',
            'jobOrder.routePlan', 'statusLogs.updatedBy:id,name',
        ]);
        return response()->json(['success' => true, 'data' => $dispatch]);
    }

    // POST /api/v1/dispatch  (admin only)
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'job_order_id'         => 'required|exists:job_orders,id',
            'driver_id'            => 'required|exists:users,id',
            'vehicle_id'           => 'nullable|integer',
            'scheduled_departure'  => 'required|date|after:now',
            'scheduled_arrival'    => 'required|date|after:scheduled_departure',
            'dispatch_notes'       => 'nullable|string',
            'checklist'            => 'nullable|array',
        ]);

        $jobOrder = JobOrder::findOrFail($request->job_order_id);

        if (!in_array($jobOrder->status, ['pending', 'assigned'])) {
            return response()->json(['success' => false, 'message' => 'Job Order tidak dalam status yang valid untuk di-dispatch.'], 422);
        }

        $dispatch = DB::transaction(function () use ($request, $jobOrder) {
            $dispatch = Dispatch::create([
                ...$request->only([
                    'job_order_id', 'driver_id', 'vehicle_id',
                    'scheduled_departure', 'scheduled_arrival',
                    'dispatch_notes', 'checklist',
                ]),
                'dispatched_by' => $request->user()->id,
                'status'        => 'confirmed',
            ]);

            // Update job order
            $jobOrder->update([
                'status'             => 'assigned',
                'assigned_driver_id' => $request->driver_id,
            ]);

            // Log status
            DispatchStatusLog::create([
                'dispatch_id' => $dispatch->id,
                'updated_by'  => $request->user()->id,
                'from_status' => 'draft',
                'to_status'   => 'confirmed',
                'notes'       => 'Dispatch dibuat dan dikonfirmasi.',
            ]);

            return $dispatch;
        });

        // Notif driver dengan suara
        Http::withHeaders(['X-Internal-Key' => config('services.fastapi.internal_key')])
            ->post(config('services.fastapi.url') . '/api/internal/notify', [
                'event'   => 'carrier.assigned',
                'user_id' => $request->driver_id,
                'sound'   => true,
                'data'    => [
                    'dispatch_id'    => $dispatch->id,
                    'dispatch_number'=> $dispatch->dispatch_number,
                    'job_number'     => $jobOrder->job_number,
                    'origin'         => $jobOrder->origin_address,
                    'destination'    => $jobOrder->destination_address,
                    'departure'      => $dispatch->scheduled_departure,
                    'message'        => 'Anda mendapat penugasan baru! Segera cek detail job.',
                ],
                'timestamp' => now()->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispatch berhasil dibuat. Driver telah dinotifikasi.',
            'data'    => $dispatch->load(['driver:id,name', 'jobOrder:id,job_number']),
        ], 201);
    }

    // PATCH /api/v1/dispatch/{id}/status
    public function updateStatus(Request $request, Dispatch $dispatch): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:departed,arrived,loading,in_transit,delivered,cancelled',
            'notes'  => 'nullable|string',
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
        ]);

        $user = $request->user();

        // Driver hanya bisa update status dispatch-nya sendiri
        if ($user->isDriver() && $dispatch->driver_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Bukan dispatch Anda.'], 403);
        }

        $allowedTransitions = [
            'confirmed'  => ['departed', 'cancelled'],
            'departed'   => ['arrived', 'loading', 'in_transit'],
            'arrived'    => ['loading', 'delivered'],
            'loading'    => ['in_transit'],
            'in_transit' => ['delivered', 'arrived'],
        ];

        if (!in_array($request->status, $allowedTransitions[$dispatch->status] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => "Tidak bisa ubah status '{$dispatch->status}' → '{$request->status}'.",
            ], 422);
        }

        $timestamps = [
            'departed'   => ['actual_departure' => now()],
            'delivered'  => ['actual_arrival'   => now()],
        ];

        DB::transaction(function () use ($request, $dispatch, $timestamps, $user) {
            $update = ['status' => $request->status];
            if (isset($timestamps[$request->status])) {
                $update = array_merge($update, $timestamps[$request->status]);
            }
            $dispatch->update($update);

            DispatchStatusLog::create([
                'dispatch_id' => $dispatch->id,
                'updated_by'  => $user->id,
                'from_status' => $dispatch->getOriginal('status'),
                'to_status'   => $request->status,
                'notes'       => $request->notes,
                'lat'         => $request->lat,
                'lng'         => $request->lng,
            ]);
        });

        // Broadcast ke semua
        Http::withHeaders(['X-Internal-Key' => config('services.fastapi.internal_key')])
            ->post(config('services.fastapi.url') . '/api/internal/notify', [
                'event'     => 'dispatch.status_updated',
                'data'      => [
                    'dispatch_id'     => $dispatch->id,
                    'dispatch_number' => $dispatch->dispatch_number,
                    'status'          => $request->status,
                    'updated_by'      => $user->name,
                    'notes'           => $request->notes,
                ],
                'timestamp' => now()->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Status dispatch diperbarui ke: {$request->status}",
            'data'    => $dispatch->fresh(),
        ]);
    }

    // DELETE /api/v1/dispatch/{id}  (admin only)
    public function destroy(Dispatch $dispatch): JsonResponse
    {
        if (!in_array($dispatch->status, ['draft', 'confirmed'])) {
            return response()->json(['success' => false, 'message' => 'Dispatch yang sudah berjalan tidak bisa dihapus.'], 422);
        }

        $dispatch->jobOrder->update(['status' => 'pending', 'assigned_driver_id' => null]);
        $dispatch->delete();

        return response()->json(['success' => true, 'message' => 'Dispatch berhasil dihapus.']);
    }
}
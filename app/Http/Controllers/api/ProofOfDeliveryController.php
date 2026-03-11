<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProofOfDelivery;
use App\Models\JobOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProofOfDeliveryController extends Controller
{
    // GET /api/pods — admin: semua, driver: punya sendiri
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = ProofOfDelivery::with([
            'jobOrder:id,job_number,customer_name,origin_city,destination_city',
            'driver:id,name,phone',
            'verifiedBy:id,name',
        ]);

        if ($user->role === 'driver') {
            $query->where('driver_id', $user->id);
        }

        $query
            ->when($request->status,       fn($q, $v) => $q->where('status', $v))
            ->when($request->job_order_id, fn($q, $v) => $q->where('job_order_id', $v))
            ->when($request->search,       fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('pod_number', 'like', "%$v%")
                    ->orWhereHas('jobOrder', fn($jq) =>
                        $jq->where('job_number', 'like', "%$v%")
                           ->orWhere('customer_name', 'like', "%$v%")
                    )
                )
            )
            ->orderBy('created_at', 'desc');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($request->per_page ?? 15),
        ]);
    }

    // GET /api/pods/{id}
    public function show(ProofOfDelivery $proofOfDelivery): JsonResponse
    {
        $proofOfDelivery->load([
            'jobOrder', 'driver:id,name,phone', 'verifiedBy:id,name'
        ]);

        return response()->json(['success' => true, 'data' => $proofOfDelivery]);
    }

    // POST /api/pods — driver submit POD
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_order_id'           => 'required|exists:job_orders,id',
            'recipient_name'         => 'required|string|max:255',
            'recipient_phone'        => 'nullable|string|max:20',
            'recipient_relationship' => 'nullable|string|max:100',
            'notes'                  => 'nullable|string',
            'delivery_lat'           => 'nullable|numeric',
            'delivery_lng'           => 'nullable|numeric',
            'photos'                 => 'nullable|array|max:5',
            'photos.*'               => 'image|max:5120', // max 5MB per foto
            'signature'              => 'nullable|string', // base64
        ]);

        $user = $request->user();

        // Cek job order valid dan milik driver ini
        $jobOrder = JobOrder::findOrFail($validated['job_order_id']);
        if ($user->role === 'driver' && $jobOrder->assigned_driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job order ini bukan milik Anda',
            ], 403);
        }

        // Upload foto jika ada
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('pods/photos', 'public');
                $photoPaths[] = $path;
            }
        }

        // Simpan signature base64 jika ada
        $signaturePath = null;
        if (!empty($validated['signature'])) {
            $signatureData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['signature']));
            $signaturePath = 'pods/signatures/' . uniqid() . '.png';
            Storage::disk('public')->put($signaturePath, $signatureData);
        }

        $pod = ProofOfDelivery::create([
            'job_order_id'           => $validated['job_order_id'],
            'driver_id'              => $user->id,
            'pod_number'             => ProofOfDelivery::generatePodNumber(),
            'status'                 => 'submitted',
            'recipient_name'         => $validated['recipient_name'],
            'recipient_phone'        => $validated['recipient_phone'] ?? null,
            'recipient_relationship' => $validated['recipient_relationship'] ?? null,
            'notes'                  => $validated['notes'] ?? null,
            'delivery_lat'           => $validated['delivery_lat'] ?? null,
            'delivery_lng'           => $validated['delivery_lng'] ?? null,
            'photos'                 => !empty($photoPaths) ? $photoPaths : null,
            'signature_path'         => $signaturePath,
            'delivered_at'           => now(),
        ]);

        // Update status job order ke delivered
        $jobOrder->update(['status' => 'delivered', 'delivery_actual_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'POD berhasil disubmit',
            'data'    => $pod->load(['jobOrder:id,job_number', 'driver:id,name']),
        ], 201);
    }

    // PATCH /api/pods/{id}/verify — admin verify POD
    public function verify(Request $request, ProofOfDelivery $proofOfDelivery): JsonResponse
    {
        if ($proofOfDelivery->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya POD dengan status submitted yang bisa diverifikasi',
            ], 422);
        }

        $proofOfDelivery->update([
            'status'      => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Update job order ke completed
        $proofOfDelivery->jobOrder->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'POD berhasil diverifikasi',
            'data'    => $proofOfDelivery->fresh(['jobOrder', 'driver', 'verifiedBy']),
        ]);
    }

    // PATCH /api/pods/{id}/reject — admin reject POD
    public function reject(Request $request, ProofOfDelivery $proofOfDelivery): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        if ($proofOfDelivery->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya POD dengan status submitted yang bisa ditolak',
            ], 422);
        }

        $proofOfDelivery->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'POD ditolak',
            'data'    => $proofOfDelivery->fresh(),
        ]);
    }

    // GET /api/pods/job-order/{jobOrderId} — cek POD by job order
    public function getByJobOrder(int $jobOrderId): JsonResponse
    {
        $pod = ProofOfDelivery::with(['driver:id,name', 'verifiedBy:id,name'])
            ->where('job_order_id', $jobOrderId)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $pod,
        ]);
    }
}
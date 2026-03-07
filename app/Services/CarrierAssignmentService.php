<?php

namespace App\Services;

use App\Models\Carrier;
use App\Models\CarrierAssignment;
use App\Models\CarrierVehicle;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class CarrierAssignmentService
{
    // ── Carrier CRUD ───────────────────────────────────────────────

    public function listCarriers(array $filters): LengthAwarePaginator
    {
        return Carrier::with('vehicles')
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['type']   ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('name', 'like', "%$v%")
                    ->orWhere('code', 'like', "%$v%")
                    ->orWhere('city', 'like', "%$v%")
                )
            )
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createCarrier(array $data): Carrier
    {
        return Carrier::create([
            ...$data,
            'code' => Carrier::generateCode(),
        ]);
    }

    public function updateCarrier(Carrier $carrier, array $data): Carrier
    {
        $carrier->update($data);
        return $carrier->fresh();
    }

    // ── Vehicle CRUD ───────────────────────────────────────────────

    public function addVehicle(Carrier $carrier, array $data): CarrierVehicle
    {
        return $carrier->vehicles()->create($data);
    }

    public function updateVehicle(CarrierVehicle $vehicle, array $data): CarrierVehicle
    {
        $vehicle->update($data);
        return $vehicle->fresh();
    }

    // ── Assignment ─────────────────────────────────────────────────

    public function listAssignments(array $filters): LengthAwarePaginator
    {
        return CarrierAssignment::with([
                'jobOrder:id,job_number,origin_city,destination_city',
                'carrier:id,name,code',
                'vehicle:id,plate_number,vehicle_type',
                'assignedBy:id,name',
            ])
            ->when($filters['status']     ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['carrier_id'] ?? null, fn($q, $v) => $q->where('carrier_id', $v))
            ->when($filters['search']     ?? null, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('assignment_number', 'like', "%$v%")
                    ->orWhereHas('jobOrder', fn($jq) =>
                        $jq->where('job_number', 'like', "%$v%")
                    )
                    ->orWhereHas('carrier', fn($cq) =>
                        $cq->where('name', 'like', "%$v%")
                    )
                )
            )
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createAssignment(array $data, User $authUser): CarrierAssignment
    {
        return DB::transaction(function () use ($data, $authUser) {
            $jo = JobOrder::findOrFail($data['job_order_id']);

            if (! in_array($jo->status, ['pending', 'assigned'])) {
                throw new \InvalidArgumentException(
                    'Job order must be pending or assigned to create an assignment.'
                );
            }

            $assignment = CarrierAssignment::create([
                ...$data,
                'assignment_number' => CarrierAssignment::generateAssignmentNumber(),
                'assigned_by'       => $authUser->id,
                'status'            => 'draft',
            ]);

            // Update vehicle status jika dipilih
            if ($data['vehicle_id'] ?? null) {
                CarrierVehicle::find($data['vehicle_id'])
                    ?->update(['status' => 'on_trip']);
            }

            return $assignment->load(['jobOrder', 'carrier', 'vehicle', 'assignedBy']);
        });
    }

    public function updateStatus(CarrierAssignment $assignment, string $newStatus, array $meta, User $authUser): CarrierAssignment
    {
        if (! $assignment->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from [{$assignment->status}] to [{$newStatus}]"
            );
        }

        return DB::transaction(function () use ($assignment, $newStatus, $meta, $authUser) {
            $updates = ['status' => $newStatus];

            match ($newStatus) {
                'sent'        => $updates['spk_sent_at'] = now(),
                'in_progress' => $updates['actual_pickup_at'] = now(),
                'completed'   => $updates['actual_delivery_at'] = now(),
                'rejected'    => $updates['rejection_reason'] = $meta['reason'] ?? null,
                default       => null,
            };

            if ($meta['agreed_price'] ?? null) {
                $updates['agreed_price'] = $meta['agreed_price'];
            }

            $assignment->update($updates);

            // Bebaskan kendaraan jika selesai/batal
            if (in_array($newStatus, ['completed', 'cancelled']) && $assignment->vehicle_id) {
                CarrierVehicle::find($assignment->vehicle_id)
                    ?->update(['status' => 'available']);
            }

            // Kalau completed, update job order juga
            if ($newStatus === 'completed') {
                $assignment->jobOrder->update([
                    'status'          => 'delivered',
                    'delivery_actual_at' => now(),
                    'actual_cost'     => $assignment->agreed_price,
                ]);

                // Recalculate rating carrier
                $assignment->carrier->updateRating();
            }

            return $assignment->fresh(['jobOrder', 'carrier', 'vehicle', 'assignedBy']);
        });
    }

    public function sendSpk(CarrierAssignment $assignment, User $authUser): CarrierAssignment
    {
        return $this->updateStatus($assignment, 'sent', [], $authUser);
    }
}
<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\JobOrderStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class JobOrderService
{
    public function list(array $filters, User $authUser): LengthAwarePaginator
    {
        $query = JobOrder::with(['creator:id,name', 'driver:id,name', 'admin:id,name'])
            ->when($authUser->role === 'driver', fn($q) =>
                $q->where('assigned_driver_id', $authUser->id)
            )
            ->when($filters['status'] ?? null, fn($q, $v) =>
                $q->where('status', $v)
            )
            ->when($filters['priority'] ?? null, fn($q, $v) =>
                $q->where('priority', $v)
            )
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where(fn($q2) => $q2
                    ->where('job_number', 'like', "%$v%")
                    ->orWhere('customer_name', 'like', "%$v%")
                    ->orWhere('origin_city', 'like', "%$v%")
                    ->orWhere('destination_city', 'like', "%$v%")
                )
            )
            ->when($filters['date_from'] ?? null, fn($q, $v) =>
                $q->whereDate('created_at', '>=', $v)
            )
            ->when($filters['date_to'] ?? null, fn($q, $v) =>
                $q->whereDate('created_at', '<=', $v)
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, User $authUser): JobOrder
    {
        return DB::transaction(function () use ($data, $authUser) {
            $jo = JobOrder::create([
                ...$data,
                'job_number' => JobOrder::generateJobNumber(),
                'created_by' => $authUser->id,
                'status'     => 'draft',
            ]);

            $this->logStatus($jo, null, 'draft', $authUser, 'Job order created');

            return $jo->load(['creator', 'driver', 'admin']);
        });
    }

    public function update(JobOrder $jo, array $data, User $authUser): JobOrder
    {
        $this->authorizeEdit($jo, $authUser);

        $jo->update($data);

        return $jo->fresh(['creator', 'driver', 'admin']);
    }

    public function updateStatus(JobOrder $jo, string $newStatus, array $meta, User $authUser): JobOrder
    {
        if (! $jo->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from [{$jo->status}] to [{$newStatus}]"
            );
        }

        return DB::transaction(function () use ($jo, $newStatus, $meta, $authUser) {
            $old = $jo->status;

            $timestamps = [
                'picked_up'  => 'pickup_actual_at',
                'delivered'  => 'delivery_actual_at',
                'in_progress'=> null,
            ];

            $updates = ['status' => $newStatus];

            if (isset($timestamps[$newStatus]) && $timestamps[$newStatus]) {
                $updates[$timestamps[$newStatus]] = now();
            }

            if ($newStatus === 'cancelled') {
                $updates['cancellation_reason'] = $meta['notes'] ?? null;
            }

            $jo->update($updates);

            $this->logStatus($jo, $old, $newStatus, $authUser, $meta['notes'] ?? null, $meta);

            return $jo->fresh(['creator', 'driver', 'admin', 'statusLogs']);
        });
    }

    public function assignDriver(JobOrder $jo, int $driverId, User $authUser): JobOrder
    {
        $driver = User::where('id', $driverId)->where('role', 'driver')->firstOrFail();

        return DB::transaction(function () use ($jo, $driver, $authUser) {
            $jo->update([
                'assigned_driver_id' => $driver->id,
                'assigned_admin_id'  => $authUser->id,
                'status'             => 'assigned',
            ]);

            $this->logStatus($jo, $jo->getOriginal('status'), 'assigned', $authUser,
                "Assigned to driver: {$driver->name}");

            return $jo->fresh(['creator', 'driver', 'admin']);
        });
    }

    public function delete(JobOrder $jo, User $authUser): void
    {
        if (! in_array($jo->status, ['draft', 'cancelled'])) {
            throw new \InvalidArgumentException('Only draft or cancelled job orders can be deleted.');
        }

        $jo->delete();
    }

    // ── Private ────────────────────────────────────────────────────

    private function logStatus(JobOrder $jo, ?string $from, string $to, User $user, ?string $notes = null, array $meta = []): void
    {
        JobOrderStatusLog::create([
            'job_order_id' => $jo->id,
            'changed_by'   => $user->id,
            'from_status'  => $from,
            'to_status'    => $to,
            'notes'        => $notes,
            'location'     => $meta['location'] ?? null,
            'lat'          => $meta['lat'] ?? null,
            'lng'          => $meta['lng'] ?? null,
        ]);
    }

    private function authorizeEdit(JobOrder $jo, User $authUser): void
    {
        if ($authUser->role === 'admin') return;

        if ($jo->created_by !== $authUser->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Forbidden');
        }
    }
}
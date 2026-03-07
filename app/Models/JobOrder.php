<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_number', 'created_by', 'assigned_driver_id', 'assigned_admin_id',
        'customer_name', 'customer_phone', 'customer_email',
        'origin_address', 'origin_city', 'origin_lat', 'origin_lng',
        'destination_address', 'destination_city', 'destination_lat', 'destination_lng',
        'cargo_type', 'cargo_weight_kg', 'cargo_volume_m3', 'cargo_description', 'is_hazardous',
        'pickup_scheduled_at', 'delivery_scheduled_at', 'pickup_actual_at', 'delivery_actual_at',
        'status', 'priority', 'estimated_cost', 'actual_cost', 'payment_status',
        'notes', 'cancellation_reason',
    ];

    protected $casts = [
        'is_hazardous'          => 'boolean',
        'pickup_scheduled_at'   => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'pickup_actual_at'      => 'datetime',
        'delivery_actual_at'    => 'datetime',
        'estimated_cost'        => 'decimal:2',
        'actual_cost'           => 'decimal:2',
        'origin_lat'            => 'decimal:7',
        'origin_lng'            => 'decimal:7',
        'destination_lat'       => 'decimal:7',
        'destination_lng'       => 'decimal:7',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(JobOrderStatusLog::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public static function generateJobNumber(): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $last  = static::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->lockForUpdate()
                        ->count();

        return sprintf('JO-%s%s-%05d', $year, $month, $last + 1);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            'draft'      => ['pending', 'cancelled'],
            'pending'    => ['assigned', 'cancelled'],
            'assigned'   => ['in_progress', 'cancelled'],
            'in_progress'=> ['picked_up', 'failed', 'cancelled'],
            'picked_up'  => ['in_transit', 'failed'],
            'in_transit' => ['delivered', 'failed'],
            'delivered'  => ['completed'],
            'completed'  => [],
            'cancelled'  => [],
            'failed'     => ['pending'],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? []);
    }
}
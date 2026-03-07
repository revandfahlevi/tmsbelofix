<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assignment_number', 'job_order_id', 'carrier_id', 'vehicle_id', 'assigned_by',
        'status', 'quoted_price', 'agreed_price', 'currency', 'payment_term', 'payment_status',
        'pickup_scheduled_at', 'delivery_scheduled_at', 'actual_pickup_at', 'actual_delivery_at',
        'spk_number', 'spk_sent_at', 'rejection_reason', 'notes',
    ];

    protected $casts = [
        'quoted_price'          => 'decimal:2',
        'agreed_price'          => 'decimal:2',
        'pickup_scheduled_at'   => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'actual_pickup_at'      => 'datetime',
        'actual_delivery_at'    => 'datetime',
        'spk_sent_at'           => 'datetime',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CarrierVehicle::class, 'vehicle_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public static function generateAssignmentNumber(): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $count = static::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count();
        return sprintf('CA-%s%s-%05d', $year, $month, $count + 1);
    }

    public function canTransitionTo(string $new): bool
    {
        $map = [
            'draft'       => ['sent', 'cancelled'],
            'sent'        => ['confirmed', 'rejected', 'cancelled'],
            'confirmed'   => ['in_progress', 'cancelled'],
            'rejected'    => ['draft'],   // bisa revisi & kirim ulang
            'in_progress' => ['completed', 'cancelled'],
            'completed'   => [],
            'cancelled'   => [],
        ];

        return in_array($new, $map[$this->status] ?? []);
    }
}
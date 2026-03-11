<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProofOfDelivery extends Model
{
    use SoftDeletes;

   protected $fillable = [
    'job_order_id', 'driver_id', 'verified_by',
    'pod_number', 'status',
    'recipient_name', 'recipient_phone', 'recipient_relationship',
    'delivered_at', 'verified_at',
    'photos', 'photo_paths', 'signature_path',
    'notes', 'delivery_notes',
    'rejection_reason', 'dispute_reason',
    'delivery_lat', 'delivery_lng',
];

protected $casts = [
    'photos'       => 'array',
    'photo_paths'  => 'array',
    'delivered_at' => 'datetime',
    'verified_at'  => 'datetime',
    'delivery_lat' => 'decimal:7',
    'delivery_lng' => 'decimal:7',
];

  

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function generatePodNumber(): string
    {
        $prefix = 'POD-' . now()->format('Ym') . '-';

        $last = static::withTrashed()
            ->where('pod_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(pod_number, -5) AS UNSIGNED) DESC')
            ->first();

        $next = $last ? (int) substr($last->pod_number, -5) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierVehicle extends Model
{
    protected $fillable = [
        'carrier_id', 'plate_number', 'vehicle_type', 'brand',
        'max_weight_kg', 'max_volume_m3', 'status',
        'stnk_expired_at', 'kir_expired_at',
    ];

    protected $casts = [
        'stnk_expired_at' => 'date',
        'kir_expired_at'  => 'date',
        'max_weight_kg'   => 'decimal:2',
        'max_volume_m3'   => 'decimal:2',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function isDocumentExpired(): bool
    {
        return ($this->stnk_expired_at && $this->stnk_expired_at->isPast())
            || ($this->kir_expired_at  && $this->kir_expired_at->isPast());
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'license_number', 'license_type', 'license_expiry',
        'vehicle_type', 'availability_status', 'current_lat', 'current_lng',
        'location_updated_at', 'total_deliveries'
    ];

    protected $casts = [
        'license_expiry'      => 'date',
        'location_updated_at' => 'datetime',
        'current_lat'         => 'decimal:8',
        'current_lng'         => 'decimal:8',
    ];

    public function user() { return $this->belongsTo(User::class); }
}   
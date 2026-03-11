<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverJobApplication extends Model
{
    protected $fillable = [
        'job_order_id', 'driver_id', 'status', 'driver_notes',
        'applied_at', 'responded_at',
    ];

    protected $casts = [
        'applied_at'    => 'datetime',
        'responded_at'  => 'datetime',
    ];

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function driver()   { return $this->belongsTo(User::class, 'driver_id'); }
}
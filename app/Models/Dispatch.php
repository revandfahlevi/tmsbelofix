<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dispatch_number', 'job_order_id', 'driver_id', 'vehicle_id',
        'dispatched_by', 'status', 'scheduled_departure', 'actual_departure',
        'scheduled_arrival', 'actual_arrival', 'dispatch_notes', 'checklist',
    ];

    protected $casts = [
        'scheduled_departure' => 'datetime',
        'actual_departure'    => 'datetime',
        'scheduled_arrival'   => 'datetime',
        'actual_arrival'      => 'datetime',
        'checklist'           => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->dispatch_number = 'DSP-' . date('Ymd') . '-' . str_pad(
                static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
            );
        });
    }

    public function jobOrder()   { return $this->belongsTo(JobOrder::class); }
    public function driver()     { return $this->belongsTo(User::class, 'driver_id'); }
    public function dispatcher() { return $this->belongsTo(User::class, 'dispatched_by'); }
    public function statusLogs() { return $this->hasMany(DispatchStatusLog::class); }
}
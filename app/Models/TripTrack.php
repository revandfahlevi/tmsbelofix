<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripTrack extends Model
{
    // Matikan created_at & updated_at sekalian
    public $timestamps = false;

    protected $fillable = [
        'job_order_id',
        'driver_id',
        'lat',
        'lng',
        'speed_kmh',
        'heading',
        'distance_from_prev_km',
        'recorded_at',
    ];

    protected $casts = [
        'lat'                   => 'decimal:8',
        'lng'                   => 'decimal:8',
        'speed_kmh'             => 'decimal:2',
        'distance_from_prev_km' => 'decimal:4',
        'recorded_at'           => 'datetime',
    ];

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function driver()   { return $this->belongsTo(User::class, 'driver_id'); }
}
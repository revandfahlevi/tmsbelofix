<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'phone', 'email', 'address', 'city',
        'pic_name', 'pic_phone', 'type', 'status', 'rating', 'notes',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(CarrierVehicle::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CarrierAssignment::class);
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->count();
        return sprintf('CAR-%03d', $last + 1);
    }

    public function updateRating(): void
    {
        $avg = $this->assignments()
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->avg('rating');

        $this->update(['rating' => round($avg ?? 0, 2)]);
    }
}
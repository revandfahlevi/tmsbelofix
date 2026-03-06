<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role',
        'status', 'avatar', 'employee_id',
        'email_verified_at', 'last_login_at', 'last_login_ip', 'fcm_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    // ===== ROLE HELPERS =====
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isDriver(): bool { return $this->role === 'driver'; }
    public function isUser(): bool { return $this->role === 'user'; }

    public function hasRole(string|array $roles): bool
    {
        return is_array($roles)
            ? in_array($this->role, $roles)
            : $this->role === $roles;
    }

    public function isActive(): bool { return $this->status === 'active'; }

    // relasi
    public function driverProfile() { return $this->hasOne(DriverProfile::class); }
    public function refreshTokens() { return $this->hasMany(RefreshToken::class); }
    public function authLogs()      { return $this->hasMany(AuthLog::class); }

    // scopes
    public function scopeActive($query)             { return $query->where('status', 'active'); }
    public function scopeByRole($query, string $role) { return $query->where('role', $role); }

    // ===== SANCTUM ABILITIES PER ROLE =====
    public function getAbilities(): array
    {
        return match($this->role) {
            'admin'  => ['*'],
            'driver' => [
                'job-order:read', 'job-order:apply',
                'dispatch:read', 'dispatch:update',
                'gps:update', 'pod:capture',
                'delivery:update-status', 'notification:receive',
            ],
            'user' => [
                'job-order:read', 'dispatch:read',
                'gps:read', 'delivery:read', 'billing:read',
            ],
            default => [],
        };
    }
}
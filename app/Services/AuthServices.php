<?php

namespace App\Services;

use App\Models\AuthLog;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'password'    => Hash::make($data['password']),
            'role'        => $data['role'],
            'employee_id' => $data['employee_id'] ?? $this->generateEmployeeId($data['role']),
            'status'      => 'active',
        ]);

        if ($user->role === 'driver' && isset($data['driver_profile'])) {
            $user->driverProfile()->create($data['driver_profile']);
        }

        $this->log($user, 'register');
        return $this->issueTokens($user, $data['device_name'] ?? 'web');
    }

    public function login(array $credentials, string $deviceName = 'web'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->logFailed($credentials['email']);
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $this->log($user, 'login');
        return $this->issueTokens($user, $deviceName);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
        RefreshToken::where('user_id', $user->id)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
        $this->log($user, 'logout');
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->update(['is_revoked' => true]);
        $this->log($user, 'logout', ['scope' => 'all_devices']);
    }

    public function refreshToken(string $refreshTokenStr): array
    {
        $refreshToken = RefreshToken::where('token', hash('sha256', $refreshTokenStr))
            ->with('user')->first();

        if (!$refreshToken || !$refreshToken->isValid()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token tidak valid atau sudah kadaluarsa.'],
            ]);
        }

        $user = $refreshToken->user;

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Akun tidak aktif.'],
            ]);
        }

        $refreshToken->update(['used_at' => now(), 'is_revoked' => true]);
        $this->log($user, 'token_refresh');

        return $this->issueTokens($user, $refreshToken->device_name ?? 'web');
    }

    private function issueTokens(User $user, string $deviceName): array
    {
        $accessToken = $user->createToken(
            name: $deviceName,
            abilities: $user->getAbilities(),
            expiresAt: now()->addHours(config('auth.token_expiry_hours', 8))
        );

        $rawRefreshToken = Str::random(80);

        RefreshToken::create([
            'user_id'     => $user->id,
            'token'       => hash('sha256', $rawRefreshToken),
            'device_name' => $deviceName,
            'expires_at'  => now()->addDays(config('auth.refresh_token_days', 30)),
        ]);

        return [
            'access_token'  => $accessToken->plainTextToken,
            'refresh_token' => $rawRefreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => config('auth.token_expiry_hours', 8) * 3600,
            'user'          => $this->formatUser($user),
        ];
    }

    private function formatUser(User $user): array
    {
        $data = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'role'        => $user->role,
            'status'      => $user->status,
            'employee_id' => $user->employee_id,
            'avatar'      => $user->avatar,
            'abilities'   => $user->getAbilities(),
        ];

        if ($user->isDriver()) {
            $data['driver_profile'] = $user->driverProfile;
        }

        return $data;
    }

    private function generateEmployeeId(string $role): string
    {
        $prefix = match($role) {
            'admin'  => 'ADM',
            'driver' => 'DRV',
            'user'   => 'USR',
            default  => 'EMP',
        };
        $number = str_pad(User::byRole($role)->count() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$number}-" . date('Y');
    }

    private function log(User $user, string $event, array $metadata = []): void
    {
        AuthLog::create([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'event'      => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata'   => $metadata ?: null,
        ]);
    }

    private function logFailed(string $email): void
    {
        AuthLog::create([
            'user_id'    => null,
            'email'      => $email,
            'event'      => 'login_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
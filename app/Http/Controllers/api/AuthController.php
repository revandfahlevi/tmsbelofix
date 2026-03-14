<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;  // ← tambahkan ini
use App\Models\User;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    // ── Login biasa (email + password) ────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'fcm_token'   => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)
                    ->with('driverProfile')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.'], 403);
        }

        // Hapus token lama
        $user->tokens()->where('name', $request->device_name ?? 'api')->delete();

        $token = $user->createToken(
            $request->device_name ?? 'api',
            $this->getAbilities($user->role)
        );

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'is_online'     => true,
            'fcm_token'     => $request->fcm_token ?? $user->fcm_token,
        ]);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user'       => new UserResource($user),
        ]);
    }

    // ── Logout ─────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $request->user()->update(['is_online' => false]);

        return response()->json(['message' => 'Berhasil logout.']);
    }

    // ── Me ──────────────────────────────────────────────────────
    public function me(Request $request)
    {
        return new UserResource(
            $request->user()->load(['driverProfile'])
        );
    }

    // ── Update FCM Token ────────────────────────────────────────
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'FCM token updated.']);
    }

    // ── Refresh Token ───────────────────────────────────────────
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('api', $this->getAbilities($user->role));

        return response()->json(['token' => $token->plainTextToken]);
    }

    // ══════════════════════════════════════════════════════════════
    // GOOGLE OAUTH
    // ══════════════════════════════════════════════════════════════

    // ── Step 1: Redirect ke halaman login Google ───────────────
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    // ── Step 2: Handle callback setelah user login di Google ───
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:8080');
            return redirect("{$frontendUrl}/login?error=google_failed");
        }

        // Cari user berdasarkan email atau google_id
        $user = User::where('email', $googleUser->getEmail())
                    ->orWhere('google_id', $googleUser->getId())
                    ->first();

        if (!$user) {
            // Buat user baru
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'password'          => bcrypt(Str::random(32)),
                'role'              => 'user',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            // Update data Google jika sudah ada
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $user->avatar ?? $googleUser->getAvatar(),
            ]);
        }

        // Cek status akun
        if ($user->status !== 'active') {
            $frontendUrl = config('app.frontend_url', 'http://localhost:8080');
            return redirect("{$frontendUrl}/login?error=account_suspended");
        }

        // Buat token baru, hapus token Google lama
        $user->tokens()->where('name', 'google-oauth')->delete();
        $token = $user->createToken('google-oauth', $this->getAbilities($user->role))
                      ->plainTextToken;

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'is_online'     => true,
        ]);

        // Kirim token ke frontend Vue via redirect
        $frontendUrl = config('app.frontend_url', 'http://localhost:8080');
        $userData    = urlencode(json_encode([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]));

        return redirect("{$frontendUrl}/auth/callback?token={$token}&user={$userData}");
    }

    // ── Helper: abilities per role ─────────────────────────────
    private function getAbilities(string $role): array
    {
        return match ($role) {
            'admin'  => ['*'],
            'driver' => ['orders:read', 'orders:update', 'gps:update', 'pod:create', 'status:update'],
            'user'   => ['orders:read', 'tracking:read', 'reports:read'],
            default  => ['orders:read'],
        };
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    // POST /api/v1/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                credentials: $request->only('email', 'password'),
                deviceName: $request->input('device_name', $request->userAgent() ?? 'web')
            );
            return response()->json(['success' => true, 'message' => 'Login berhasil.', 'data' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Kredensial tidak valid.', 'errors' => $e->errors()], 401);
        }
    }

    // POST /api/v1/admin/register  (admin only — dicek di route middleware)
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json(['success' => true, 'message' => 'Akun berhasil dibuat.', 'data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat akun: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/v1/auth/refresh
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken($request->input('refresh_token'));
            return response()->json(['success' => true, 'message' => 'Token berhasil diperbarui.', 'data' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Refresh token tidak valid.', 'errors' => $e->errors()], 401);
        }
    }

    // POST /api/v1/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    // POST /api/v1/auth/logout-all
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        return response()->json(['success' => true, 'message' => 'Logout dari semua perangkat berhasil.']);
    }

    // GET /api/v1/auth/me
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('driverProfile');
        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'role'           => $user->role,
                'status'         => $user->status,
                'employee_id'    => $user->employee_id,
                'avatar'         => $user->avatar,
                'last_login_at'  => $user->last_login_at,
                'abilities'      => $user->getAbilities(),
                'driver_profile' => $user->isDriver() ? $user->driverProfile : null,
            ],
        ]);
    }

    // PUT /api/v1/auth/update-fcm-token
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true, 'message' => 'FCM token berhasil diperbarui.']);
    }

    // PUT /api/v1/auth/change-password
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password lama tidak benar.'], 422);
        }

        $user->update(['password' => $request->new_password]);
        $user->tokens()->delete(); // force re-login

        return response()->json(['success' => true, 'message' => 'Password berhasil diubah. Silakan login kembali.']);
    }
}
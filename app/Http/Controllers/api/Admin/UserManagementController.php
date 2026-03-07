<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    // GET /api/v1/admin/users
    public function index(Request $request): JsonResponse
    {
        $query = User::with('driverProfile')->withTrashed($request->boolean('with_deleted'));

        if ($request->filled('role'))   $query->byRole($request->role);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'LIKE', "%$s%")
                ->orWhere('email', 'LIKE', "%$s%")
                ->orWhere('employee_id', 'LIKE', "%$s%"));
        }

        return response()->json(['success' => true, 'data' => $query->latest()->paginate($request->input('per_page', 15))]);
    }

    // GET /api/v1/admin/users/{user}
    public function show(User $user): JsonResponse
    {
        $user->load(['driverProfile', 'authLogs' => fn($q) => $q->latest()->limit(10)]);
        return response()->json(['success' => true, 'data' => $user]);
    }

    // PUT /api/v1/admin/users/{user}
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
        $user->update($validated);
        return response()->json(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.', 'data' => $user->fresh()]);
    }

    // DELETE /api/v1/admin/users/{user}
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }
        $user->tokens()->delete();
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Akun berhasil dihapus.']);
    }

    // PATCH /api/v1/admin/users/{user}/status
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(['status' => ['required', Rule::in(['active', 'inactive', 'suspended'])]]);
        $user->update(['status' => $request->status]);
        if ($request->status !== 'active') $user->tokens()->delete();
        return response()->json(['success' => true, 'message' => "Status akun diubah ke: {$request->status}", 'data' => $user->fresh()]);
    }

    // GET /api/v1/admin/users/{user}/auth-logs
    public function authLogs(User $user): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $user->authLogs()->latest()->paginate(20)]);
    }
}
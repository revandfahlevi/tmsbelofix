<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // Usage: ->middleware('role:admin') or ->middleware('role:admin,driver')
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->hasRole($roles)) {
            return response()->json([
                'success'        => false,
                'message'        => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                'your_role'      => $request->user()->role,
                'required_roles' => $roles,
            ], 403);
        }

        if (!$request->user()->isActive()) {    
            return response()->json(['success' => false, 'message' => 'Akun Anda tidak aktif.'], 403);
        }

        return $next($request);
    }
}
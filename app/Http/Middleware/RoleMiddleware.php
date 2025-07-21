<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user()->load('role'); // Load the role relationship
        
        // Debug logging
        \Log::info('RoleMiddleware Debug', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'role_id' => $user->role_id,
            'role_name' => $user->role?->name,
            'expected_role' => $role,
            'token' => $request->bearerToken(),
        ]);
        
        if (!$user->role || $user->role->name !== $role) {
            return response()->json([
                'message' => 'Forbidden - Insufficient permissions',
                'debug' => [
                    'user_role' => $user->role?->name,
                    'expected_role' => $role,
                    'user_id' => $user->id
                ]
            ], 403);
        }

        return $next($request);
    }
}
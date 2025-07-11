<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\Pipeline;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Get the user's mess role
        $messUser = $request->user()->messUser;
        if (!$messUser || !$messUser->role) {
            return response()->json([
                'success' => false,
                'message' => 'User has no assigned role'
            ], 403);
        }

        // Check if the user's role is among the allowed roles
        $userRole = $messUser->role->role ?? '';
        $isAdmin = $messUser->role->is_admin ?? false;

        // Admin override - always allow admins
        if ($isAdmin) {
            return $next($request);
        }

        // Check if the user's role is in the list of allowed roles
        foreach ($roles as $role) {
            if ($userRole === $role) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have the required role to access this resource'
        ], 403);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\MessUserRole;
use App\Exceptions\PermissionDeniedException;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Retrieve the current user
        $user = UserService::currentUser();

        // Check if the user exists and has the required permissions
        if (!$user || !$this->hasPermissions($user, $permissions)) {
            throw new PermissionDeniedException();
        }

        return $next($request);
    }

    /**
     * Check if the user has all required permissions.
     *
     * @param  $user
     * @param  array $permissions
     * @return bool
     */
    private function hasPermissions($user, array $permissions): bool
    {
        $userPermissions = $user->role->permissions->pluck('permission')->toArray();

        if(in_array(MessUserRole::Admin->value, $userPermissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }
}

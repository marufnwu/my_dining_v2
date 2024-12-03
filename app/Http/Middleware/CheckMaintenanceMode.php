<?php

namespace App\Http\Middleware;

use App\Exceptions\MaintenanceModeException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (app()->isDownForMaintenance()) {
            throw new MaintenanceModeException();
        }

        return $next($request);
    }
}

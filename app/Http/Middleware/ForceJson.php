<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ForceJson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force the request to expect JSON
        $request->headers->set('Accept', 'application/json');

        // Also set Content-Type if not already set
        if (!$request->headers->has('Content-Type')) {
            $request->headers->set('Content-Type', 'application/json');
        }

        return $next($request);
    }
}

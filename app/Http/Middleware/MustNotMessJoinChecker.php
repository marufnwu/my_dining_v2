<?php

namespace App\Http\Middleware;

use App\Exceptions\MustNotMessJoinException;
use App\Exceptions\NoMessException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustNotMessJoinChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->activeMess) {
             throw new MustNotMessJoinException();
        }
        return $next($request);
    }
}

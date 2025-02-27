<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtractHeaderData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract mess_id and month_id from headers
        $messId = $request->header('Mess-ID');
        $monthId = $request->header('Month-ID');

        // Store the values in the Laravel container
        app()->instance('mess_id', $messId);
        app()->instance('month_id', $monthId);

        // Proceed to the next middleware/request
        return $next($request);
    }
}

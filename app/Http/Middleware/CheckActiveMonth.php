<?php

namespace App\Http\Middleware;

use App\Exceptions\CustomException;
use App\Services\MonthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveMonth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if(!$request->hasHeader("Month-ID")){
            throw new CustomException(message: "Selected month is undefined!");
        }

        $monthId = $request->header("Month-ID");

        $month = MonthService::getSelectedMonth($monthId);

        if(!$month){
            throw new CustomException(message: "Selected month is not found");
        }

        if(!$month->isActive){
            throw new CustomException(message: "Selected month is not active");
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Exceptions\CustomException;
use App\Services\MessService;
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
    public function handle(Request $request, Closure $next,  $isCheckActiveMonth = false): Response
    {
        $isCheckActiveMonth = filter_var($isCheckActiveMonth, FILTER_VALIDATE_BOOLEAN);
        if(!$request->hasHeader("Month-ID")){
            throw new CustomException(message: "Selected month is undefined!");
        }


        $monthId = $request->header("Month-ID");

        $month = app()->getMess()->months()->where("id", $monthId)->first();

        if(!$month){
            throw new CustomException(message: "Selected month is not found");
        }



        if($isCheckActiveMonth){
            if(!$month->isActive){
                throw new CustomException(message: "Selected month is not active");
            }
        }

        app()->setMonth($month);

        return $next($request);
    }
}

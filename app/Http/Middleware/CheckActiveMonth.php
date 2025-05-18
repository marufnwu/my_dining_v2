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
    public function handle(Request $request, Closure $next, $isCheckActiveMonth = false): Response
    {
        $isCheckActiveMonth = filter_var($isCheckActiveMonth, FILTER_VALIDATE_BOOLEAN);

        // Try to get month ID from different sources in priority order
        $monthId = $this->getMonthIdFromRequest($request);

        if (!$monthId) {
            throw new CustomException(message: "Selected month is undefined!");
        }

        $month = app()->getMess()->months()->where("id", $monthId)->first();

        if (!$month) {
            throw new CustomException(message: "Selected month is not found");
        }

        if ($isCheckActiveMonth) {
            if (!$month->isActive) {
                throw new CustomException(message: "Selected month is not active");
            }
        }

        app()->setMonth($month);

        return $next($request);
    }

    /**
     * Get month ID from request using various sources
     * Priority: Route parameter > Query parameter > Request body > Header
     *
     * @param Request $request
     * @return int|null
     */
    protected function getMonthIdFromRequest(Request $request): ?int
    {
        // Check if month parameter exists in route parameters
        // This will handle route patterns like /months/{month}/something
        if ($request->route('month')) {
            $routeMonth = $request->route('month');
            // If the route parameter is a Month model instance, get its ID
            return is_object($routeMonth) ? $routeMonth->id : $routeMonth;
        }

        // Check if 'month_id' exists in query parameters
        // This will handle requests like /something?month_id=123
        if ($request->query('month_id')) {
            return $request->query('month_id');
        }

        // Check if 'month_id' exists in request body
        // This will handle form submissions and JSON requests with month_id in the payload
        if ($request->has('month_id')) {
            return $request->input('month_id');
        }

        // Finally, check the "Month-ID" header
        // This will be the fallback for your Android retrofit setup
        if ($request->hasHeader("Month-ID")) {
            return $request->header("Month-ID");
        }

        // If no month ID found anywhere, return null
        return null;
    }
}

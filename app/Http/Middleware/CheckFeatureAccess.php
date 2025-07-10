<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\CustomException;

class CheckFeatureAccess
{
    protected $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        $mess = app()->getMess();
        if (!$mess) {
            throw new CustomException('No mess context found');
        }

        $pipeline = $this->featureService->canUseFeature($mess, $featureName);
        if (!$pipeline->isSuccess()) {
            throw new CustomException($pipeline->getMessage() ?? 'Feature access denied');
        }

        return $next($request);
    }
}

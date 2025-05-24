<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckMessFeatureAccess
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature
     * @return mixed
     */
    public function handle($request, Closure $next, $feature)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get the current mess from the request or session
        $mess = $request->mess;

        if (!$mess) {
            // Attempt to get mess ID from route parameter
            $messId = $request->route('mess');

            if ($messId) {
                $mess = \App\Models\Mess::find($messId);
            }
        }

        if (!$mess) {
            return redirect()->route('messes.index')
                ->with('error', 'Please select a mess first.');
        }

        // Check if the user is a member of this mess
        $messUser = $mess->messUsers()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$messUser) {
            return redirect()->route('messes.index')
                ->with('error', 'You are not a member of this mess.');
        }

        // Check if mess has access to the feature
        if (!$this->subscriptionService->hasFeatureAccess($mess, $feature)) {
            return redirect()->route('subscriptions.index', ['mess' => $mess->id])
                ->with('error', "Your current mess plan doesn't have access to this feature.");
        }

        // Add mess to request for controllers
        $request->merge(['current_mess' => $mess]);

        return $next($request);
    }
}

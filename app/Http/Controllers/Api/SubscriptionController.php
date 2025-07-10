<?php

namespace App\Http\Controllers\Api;

use App\Facades\Feature;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionUpgradeRequest;
use App\Models\Plan;
use App\Models\PlanPackage;
use App\Services\FeatureService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $featureService;
    protected $paymentService;

    public function __construct(
        FeatureService $featureService,
        PaymentService $paymentService
    ) {
        $this->featureService = $featureService;
        $this->paymentService = $paymentService;
    }

    /**
     * Get current subscription status
     */
    public function getStatus()
    {
        $mess = app()->getMess();
        $subscription = $mess->subscription;

        if (!$subscription) {
            return response()->json([
                'active' => false,
                'message' => 'No subscription found'
            ]);
        }

        return response()->json([
            'active' => $subscription->isActiveOrInGrace(),
            'plan' => $subscription->plan->name,
            'package' => $subscription->package->duration . ' days',
            'status' => $subscription->status,
            'expires_at' => $subscription->expires_at,
            'grace_period_ends_at' => $subscription->grace_period_ends_at,
            'is_trial' => $subscription->onTrial(),
            'is_canceled' => $subscription->is_canceled,
        ]);
    }

    /**
     * List available features for current subscription
     */
    public function getFeatures()
    {
        $mess = app()->getMess();
        $pipeline = $this->featureService->getAvailableFeatures($mess);
        return $pipeline->toApiResponse();
    }

    /**
     * Get feature usage statistics
     */
    public function getUsageStats()
    {
        $mess = app()->getMess();
        $subscription = $mess->subscription;

        if (!$subscription) {
            return response()->json([
                'error' => 'No subscription found'
            ], 404);
        }

        $usageStats = $subscription->featureUsages()
            ->with('feature')
            ->get()
            ->map(function ($usage) {
                return [
                    'feature' => $usage->feature->name,
                    'used' => $usage->used,
                    'limit' => $usage->feature->usage_limit,
                    'remaining' => $usage->feature->usage_limit - $usage->used
                ];
            });

        return response()->json(['data' => $usageStats]);
    }

    /**
     * Upgrade subscription to a new plan
     */
    public function upgrade(SubscriptionUpgradeRequest $request)
    {
        $mess = app()->getMess();
        $subscription = $mess->subscription;

        if (!$subscription) {
            return response()->json([
                'error' => 'No existing subscription found'
            ], 404);
        }

        $newPlan = Plan::findOrFail($request->plan_id);
        $newPackage = PlanPackage::findOrFail($request->package_id);

        // Create new order for upgrade
        $order = $subscription->createOrder([
            'plan_id' => $newPlan->id,
            'plan_package_id' => $newPackage->id,
            'amount' => $newPackage->price,
            'payment_method_id' => $request->payment_method_id,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Upgrade order created successfully',
            'order' => $order->fresh()->load(['plan', 'package'])
        ]);
    }

    /**
     * Cancel current subscription
     */
    public function cancel()
    {
        $mess = app()->getMess();
        $subscription = $mess->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return response()->json([
                'error' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'is_canceled' => true,
            'canceled_at' => now(),
            'status' => $subscription->onTrial()
                ? Subscription::STATUS_TRIAL
                : Subscription::STATUS_CANCELED
        ]);

        return response()->json([
            'message' => 'Subscription canceled successfully',
            'expires_at' => $subscription->expires_at
        ]);
    }

    /**
     * Resume a canceled subscription
     */
    public function resume()
    {
        $mess = app()->getMess();
        $subscription = $mess->subscription;

        if (!$subscription || !$subscription->is_canceled) {
            return response()->json([
                'error' => 'No canceled subscription found'
            ], 404);
        }

        // Only allow resume if subscription hasn't expired
        if ($subscription->hasExpired()) {
            return response()->json([
                'error' => 'Subscription has expired and cannot be resumed'
            ], 400);
        }

        $subscription->update([
            'is_canceled' => false,
            'canceled_at' => null,
            'status' => $subscription->onTrial()
                ? Subscription::STATUS_TRIAL
                : Subscription::STATUS_ACTIVE
        ]);

        return response()->json([
            'message' => 'Subscription resumed successfully',
            'expires_at' => $subscription->expires_at
        ]);
    }
}

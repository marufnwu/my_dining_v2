<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanPackage;
use App\Models\Subscription;
use App\Models\FeatureUsage;
use App\Models\Mess;
use App\Models\SubscriptionOrder;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Constants\Feature;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class SubscriptionService
{
    /**
     * Create a new subscription for a mess.
     *
     * @param Mess $mess
     * @param Plan $plan
     * @param PlanPackage $package
     * @param array $paymentData
     * @return Subscription
     */
    public function subscribe(Mess $mess, Plan $plan, PlanPackage $package, array $paymentData = [])
    {
        return DB::transaction(function () use ($mess, $plan, $package, $paymentData) {
            // Calculate dates
            $startsAt = Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($package->duration);
            $nextBillingDate = $package->is_trial ? $expiresAt : $expiresAt->copy()->subDays(7);

            // Calculate grace period end date
            $gracePeriodEndsAt = $expiresAt->copy()->addDays($package->default_grace_period_days);

            // Determine if trial
            $status = $package->is_trial ? Subscription::STATUS_TRIAL : Subscription::STATUS_ACTIVE;
            $trialEndsAt = $package->is_trial ? $expiresAt : null;

            // Create subscription
            $subscription = Subscription::create([
                'mess_id' => $mess->id,
                'plan_id' => $plan->id,
                'plan_package_id' => $package->id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'trial_ends_at' => $trialEndsAt,
                'grace_period_ends_at' => $gracePeriodEndsAt,
                'admin_grace_period_days' => 0,
                'status' => $status,
                'payment_method' => $paymentData['payment_method'] ?? null,
                'payment_id' => $paymentData['payment_id'] ?? null,
                'is_canceled' => false,
                'payment_status' => $package->is_free || $plan->is_free ?
                    Subscription::PAYMENT_STATUS_PAID : Subscription::PAYMENT_STATUS_PENDING,
                'billing_cycle' => $package->duration > 180 ? 'yearly' :
                    ($package->duration > 30 ? 'monthly' : 'onetime'),
                'next_billing_date' => $nextBillingDate,
                'total_spent' => 0,
                'invoice_reference' => null,
            ]);

            // Initialize feature usages
            $this->initializeFeatureUsages($subscription);

            // Create order
            $order = $subscription->createOrder([
                'status' => $package->is_free || $plan->is_free ?
                    SubscriptionOrder::STATUS_COMPLETED : SubscriptionOrder::STATUS_PENDING,
                'payment_status' => $package->is_free || $plan->is_free ? 'paid' : 'pending',
                'billing_email' => $paymentData['billing_email'] ?? null,
                'billing_address' => $paymentData['billing_address'] ?? null,
            ]);

            // Create transaction if payment provided
            if (!empty($paymentData['payment_method'])) {
                $transaction = $subscription->recordTransaction([
                    'order_id' => $order->id,
                    'payment_method' => $paymentData['payment_method'],
                    'payment_provider' => $paymentData['payment_provider'] ?? null,
                    'payment_provider_reference' => $paymentData['payment_provider_reference'] ?? null,
                    'status' => $package->is_free || $plan->is_free ?
                        Transaction::STATUS_COMPLETED : Transaction::STATUS_PENDING,
                    'processed_at' => $package->is_free || $plan->is_free ? now() : null,
                ]);
            }

            // Generate invoice
            $invoice = $subscription->generateInvoice();
            $subscription->update(['invoice_reference' => $invoice->invoice_number]);

            // Apply features to mess
            $this->applyFeaturesOnMess($mess, $plan);

            return $subscription;
        });
    }

    /**
     * Enter grace period for expired subscription.
     *
     * @param Subscription $subscription
     * @return Subscription
     */
    public function enterGracePeriod(Subscription $subscription)
    {
        if (!$subscription->hasExpired() || $subscription->is_canceled) {
            return $subscription;
        }

        // Calculate grace period end date
        $gracePeriodEndsAt = $subscription->calculateGracePeriodEndDate();

        $subscription->update([
            'status' => Subscription::STATUS_GRACE_PERIOD,
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        // Create grace period order record
        $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_PENDING,
            'payment_status' => 'pending',
            'amount' => 0,
            'notes' => 'Subscription entered grace period - payment overdue',
        ]);

        return $subscription;
    }

    /**
     * Extend admin grace period for a subscription.
     *
     * @param Subscription $subscription
     * @param int $additionalDays
     * @return Subscription
     */
    public function extendAdminGracePeriod(Subscription $subscription, int $additionalDays)
    {
        $subscription->admin_grace_period_days += $additionalDays;

        // Recalculate grace period end date
        $newGracePeriodEndsAt = $subscription->calculateGracePeriodEndDate();
        $subscription->grace_period_ends_at = $newGracePeriodEndsAt;

        $subscription->save();

        // Create extension order record
        $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_COMPLETED,
            'payment_status' => 'completed',
            'amount' => 0,
            'notes' => "Admin extended grace period by {$additionalDays} days",
        ]);

        return $subscription;
    }

    /**
     * Set admin grace period for a subscription.
     *
     * @param Subscription $subscription
     * @param int $totalAdminDays
     * @return Subscription
     */
    public function setAdminGracePeriod(Subscription $subscription, int $totalAdminDays)
    {
        $oldAdminDays = $subscription->admin_grace_period_days;
        $subscription->admin_grace_period_days = $totalAdminDays;

        // Recalculate grace period end date
        $newGracePeriodEndsAt = $subscription->calculateGracePeriodEndDate();
        $subscription->grace_period_ends_at = $newGracePeriodEndsAt;

        $subscription->save();

        $action = $totalAdminDays > $oldAdminDays ? 'increased' : 'decreased';
        $difference = abs($totalAdminDays - $oldAdminDays);

        // Create admin action order record
        $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_COMPLETED,
            'payment_status' => 'completed',
            'amount' => 0,
            'notes' => "Admin {$action} grace period by {$difference} days to {$totalAdminDays} total admin days",
        ]);

        return $subscription;
    }

    /**
     * Check if subscription should enter grace period.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function shouldEnterGracePeriod(Subscription $subscription): bool
    {
        return $subscription->hasExpired() &&
               !$subscription->inGracePeriod() &&
               !$subscription->is_canceled &&
               $subscription->payment_status !== Subscription::PAYMENT_STATUS_PAID;
    }

    /**
     * Check if subscription grace period has expired.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function shouldExpireFromGrace(Subscription $subscription): bool
    {
        return $subscription->inGracePeriod() &&
               $subscription->gracePeriodExpired();
    }

    /**
     * Expire subscription after grace period.
     *
     * @param Subscription $subscription
     * @return Subscription
     */
    public function expireFromGracePeriod(Subscription $subscription)
    {
        $subscription->update([
            'status' => Subscription::STATUS_EXPIRED,
        ]);

        // Create expiration order record
        $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_CANCELLED,
            'payment_status' => 'failed',
            'amount' => 0,
            'notes' => 'Subscription expired after grace period ended',
        ]);

        // Revert mess features
        $this->revertFeaturesOnMess($subscription->mess);

        return $subscription;
    }

    /**
     * Apply payment to a subscription (including grace period recovery).
     *
     * @param Subscription $subscription
     * @param array $paymentData
     * @return bool
     */
    public function applyPayment(Subscription $subscription, array $paymentData)
    {
        return DB::transaction(function () use ($subscription, $paymentData) {
            // Update order
            $latestOrder = $subscription->latestOrder;
            if ($latestOrder && $latestOrder->status !== SubscriptionOrder::STATUS_COMPLETED) {
                $latestOrder->markAsComplete();
            }

            // Update or create transaction
            $transaction = $subscription->latestTransaction;
            if ($transaction && $transaction->status !== Transaction::STATUS_COMPLETED) {
                $transaction->markAsComplete($paymentData['payment_provider_reference'] ?? null);
            } else {
                $transaction = $subscription->recordTransaction([
                    'order_id' => $latestOrder->id ?? null,
                    'payment_method' => $paymentData['payment_method'],
                    'payment_provider' => $paymentData['payment_provider'] ?? null,
                    'payment_provider_reference' => $paymentData['payment_provider_reference'] ?? null,
                    'status' => Transaction::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);
            }

            // Update invoice
            $invoice = $subscription->invoices()->latest()->first();
            if ($invoice && $invoice->status !== Invoice::STATUS_PAID) {
                $invoice->markAsPaid();
            }

            // If subscription was in grace period, reactivate it
            if ($subscription->inGracePeriod()) {
                $subscription->status = Subscription::STATUS_ACTIVE;
            }

            // Update subscription
            $subscription->payment_status = Subscription::PAYMENT_STATUS_PAID;
            $subscription->payment_method = $paymentData['payment_method'];
            $subscription->payment_id = $paymentData['payment_id'] ?? $subscription->payment_id;

            return $subscription->save();
        });
    }

    /**
     * Apply features to mess settings.
     *
     * @param Mess $mess
     * @param Plan $plan
     * @return void
     */
    protected function applyFeaturesOnMess(Mess $mess, Plan $plan)
    {
        $features = $plan->features->pluck('name')->toArray();

        // Update mess settings based on plan features
        if (in_array('ad_free', $features)) {
            $mess->ad_free = true;
        }

        if (in_array('fund_add', $features)) {
            $mess->fund_add_enabled = true;
        }

        $mess->save();
    }

    /**
     * Initialize feature usages for a subscription.
     *
     * @param Subscription $subscription
     * @return void
     */
    protected function initializeFeatureUsages(Subscription $subscription)
    {
        $features = $subscription->plan->features;

        foreach ($features as $feature) {
            FeatureUsage::create([
                'subscription_id' => $subscription->id,
                'plan_feature_id' => $feature->id,
                'used_count' => 0,
                'reset_at' => $subscription->expires_at,
            ]);
        }
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @param bool $immediately
     * @return Subscription
     */
    public function cancel(Subscription $subscription, $immediately = false)
    {
        if ($immediately) {
            $subscription->status = Subscription::STATUS_CANCELED;
            $subscription->expires_at = Carbon::now();
        }

        $subscription->is_canceled = true;
        $subscription->canceled_at = Carbon::now();
        $subscription->save();

        // Create cancellation order/transaction record
        $order = $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_CANCELLED,
            'payment_status' => 'cancelled',
            'amount' => 0,
            'notes' => 'Subscription canceled ' . ($immediately ? 'immediately' : 'effective at end of billing period'),
        ]);

        // Revert mess settings if canceling immediately
        if ($immediately) {
            $this->revertFeaturesOnMess($subscription->mess);
        }

        return $subscription;
    }

    /**
     * Revert features on mess when subscription is canceled.
     *
     * @param Mess $mess
     * @return void
     */
    protected function revertFeaturesOnMess(Mess $mess)
    {
        // Set defaults for free tier
        $mess->ad_free = false;
        $mess->fund_add_enabled = false;
        $mess->save();
    }

    /**
     * Renew a subscription.
     *
     * @param Subscription $subscription
     * @param array $paymentData
     * @return Subscription
     */
    public function renew(Subscription $subscription, array $paymentData = [])
    {
        return DB::transaction(function () use ($subscription, $paymentData) {
            $package = $subscription->package;
            $plan = $subscription->plan;
            $mess = $subscription->mess;

            $startsAt = $subscription->expires_at < Carbon::now() ? Carbon::now() : $subscription->expires_at;
            $expiresAt = $startsAt->copy()->addDays($package->duration);
            $nextBillingDate = $expiresAt->copy()->subDays(7);

            // Update subscription dates and status
            $subscription->starts_at = $startsAt;
            $subscription->expires_at = $expiresAt;
            $subscription->status = Subscription::STATUS_ACTIVE;
            $subscription->is_canceled = false;
            $subscription->canceled_at = null;
            $subscription->next_billing_date = $nextBillingDate;

            if (!empty($paymentData['payment_method'])) {
                $subscription->payment_method = $paymentData['payment_method'];
            }

            if (!empty($paymentData['payment_id'])) {
                $subscription->payment_id = $paymentData['payment_id'];
            }

            $subscription->save();

            // Create renewal order
            $order = $subscription->createOrder([
                'status' => $package->is_free || $plan->is_free ?
                    SubscriptionOrder::STATUS_COMPLETED : SubscriptionOrder::STATUS_PENDING,
                'payment_status' => $package->is_free || $plan->is_free ? 'paid' : 'pending',
                'billing_email' => $paymentData['billing_email'] ?? null,
                'billing_address' => $paymentData['billing_address'] ?? null,
                'notes' => 'Subscription renewal',
            ]);

            // Create transaction if payment provided
            if (!empty($paymentData['payment_method'])) {
                $transaction = $subscription->recordTransaction([
                    'order_id' => $order->id,
                    'payment_method' => $paymentData['payment_method'],
                    'payment_provider' => $paymentData['payment_provider'] ?? null,
                    'payment_provider_reference' => $paymentData['payment_provider_reference'] ?? null,
                    'status' => $package->is_free || $plan->is_free ?
                        Transaction::STATUS_COMPLETED : Transaction::STATUS_PENDING,
                    'processed_at' => $package->is_free || $plan->is_free ? now() : null,
                    'notes' => 'Subscription renewal payment',
                ]);
            }

            // Generate invoice
            $invoice = $subscription->generateInvoice();
            $subscription->update(['invoice_reference' => $invoice->invoice_number]);

            // Reset feature usages
            $this->resetFeatureUsages($subscription);

            // Reapply features to mess
            $this->applyFeaturesOnMess($mess, $plan);

            return $subscription;
        });
    }

    /**
     * Reset feature usages for a subscription.
     *
     * @param Subscription $subscription
     * @return void
     */
    protected function resetFeatureUsages(Subscription $subscription)
    {
        foreach ($subscription->featureUsages as $usage) {
            $usage->used_count = 0;
            $usage->reset_at = $subscription->expires_at;
            $usage->save();
        }
    }

    /**
     * Change plan for an existing subscription.
     *
     * @param Subscription $subscription
     * @param Plan $newPlan
     * @param PlanPackage $newPackage
     * @param bool $prorated
     * @param array $paymentData
     * @return Subscription
     */
    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        PlanPackage $newPackage,
        $prorated = true,
        array $paymentData = []
    ) {
        return DB::transaction(function () use (
            $subscription, $newPlan, $newPackage, $prorated, $paymentData
        ) {
            $mess = $subscription->mess;
            $oldPlan = $subscription->plan;
            $oldPackage = $subscription->package;

            // Handle proration logic if needed
            $startsAt = Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($newPackage->duration);
            $nextBillingDate = $expiresAt->copy()->subDays(7);

            // Calculate prorated amount if applicable
            $proratedAmount = $newPackage->price;
            $proratedNotes = null;

            if ($prorated && !$subscription->hasExpired() && $oldPackage->price > 0) {
                $daysLeft = $subscription->expires_at->diffInDays($startsAt);
                $oldDailyRate = $oldPackage->price / $oldPackage->duration;
                $refundAmount = $daysLeft * $oldDailyRate;

                $proratedAmount = $newPackage->price - $refundAmount;
                if ($proratedAmount < 0) $proratedAmount = 0;

                $proratedNotes = "Plan changed from {$oldPlan->name} to {$newPlan->name}. " .
                                 "Prorated refund: \${$refundAmount} for {$daysLeft} unused days.";
            }

            // Update subscription
            $subscription->plan_id = $newPlan->id;
            $subscription->plan_package_id = $newPackage->id;
            $subscription->starts_at = $startsAt;
            $subscription->expires_at = $expiresAt;
            $subscription->status = Subscription::STATUS_ACTIVE;
            $subscription->is_canceled = false;
            $subscription->canceled_at = null;
            $subscription->next_billing_date = $nextBillingDate;
            $subscription->payment_status = $newPackage->is_free || $newPlan->is_free ?
                Subscription::PAYMENT_STATUS_PAID : Subscription::PAYMENT_STATUS_PENDING;

            if (!empty($paymentData['payment_method'])) {
                $subscription->payment_method = $paymentData['payment_method'];
            }

            if (!empty($paymentData['payment_id'])) {
                $subscription->payment_id = $paymentData['payment_id'];
            }

            $subscription->save();

            // Create plan change order
            $order = $subscription->createOrder([
                'status' => $newPackage->is_free || $newPlan->is_free ?
                    SubscriptionOrder::STATUS_COMPLETED : SubscriptionOrder::STATUS_PENDING,
                'payment_status' => $newPackage->is_free || $newPlan->is_free ? 'paid' : 'pending',
                'amount' => $proratedAmount,
                'total_amount' => $proratedAmount,
                'billing_email' => $paymentData['billing_email'] ?? null,
                'billing_address' => $paymentData['billing_address'] ?? null,
                'notes' => $proratedNotes ?? "Plan changed from {$oldPlan->name} to {$newPlan->name}",
            ]);

            // Create transaction if payment provided
            if (!empty($paymentData['payment_method']) && $proratedAmount > 0) {
                $transaction = $subscription->recordTransaction([
                    'order_id' => $order->id,
                    'amount' => $proratedAmount,
                    'payment_method' => $paymentData['payment_method'],
                    'payment_provider' => $paymentData['payment_provider'] ?? null,
                    'payment_provider_reference' => $paymentData['payment_provider_reference'] ?? null,
                    'status' => $newPackage->is_free || $newPlan->is_free ?
                        Transaction::STATUS_COMPLETED : Transaction::STATUS_PENDING,
                    'processed_at' => $newPackage->is_free || $newPlan->is_free ? now() : null,
                    'notes' => $proratedNotes ?? "Plan change payment",
                ]);
            }

            // Generate invoice
            $invoice = $subscription->generateInvoice();
            $subscription->update(['invoice_reference' => $invoice->invoice_number]);

            // Remove old feature usages
            $subscription->featureUsages()->delete();

            // Initialize new feature usages
            $this->initializeFeatureUsages($subscription);

            // Apply new features to mess
            $this->applyFeaturesOnMess($mess, $newPlan);

            return $subscription;
        });
    }

    /**
     * Check if a mess has access to a specific feature (including grace period).
     *
     * @param Mess $mess
     * @param string $featureName
     * @return bool
     */
    public function hasFeatureAccess(Mess $mess, $featureName)
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            return false;
        }

        // Allow feature access during grace period
        if (!$subscription->isActiveOrInGrace()) {
            return false;
        }

        $planFeature = $subscription->plan->features()
            ->where('name', $featureName)
            ->first();

        if (!$planFeature) {
            return false;
        }

        if (!$planFeature->is_countable) {
            return true;
        }

        $featureUsage = $subscription->featureUsages()
            ->where('plan_feature_id', $planFeature->id)
            ->first();

        if (!$featureUsage) {
            return false;
        }

        return $featureUsage->withinLimits();
    }

    /**
     * Record usage of a feature.
     *
     * @param Mess $mess
     * @param string $featureName
     * @param int $amount
     * @return bool
     */
    public function recordFeatureUsage(Mess $mess, $featureName, $amount = 1)
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            return false;
        }

        $planFeature = $subscription->plan->features()
            ->where('name', $featureName)
            ->first();

        if (!$planFeature || !$planFeature->is_countable) {
            return true;
        }

        $featureUsage = $subscription->featureUsages()
            ->where('plan_feature_id', $planFeature->id)
            ->first();

        if (!$featureUsage) {
            return false;
        }

        // Check if within limits
        if (($featureUsage->used_count + $amount) > $planFeature->usage_limit) {
            return false;
        }

        return $featureUsage->increment($amount);
    }

    /**
     * Check if a mess can add more users based on member limit.
     *
     * @param Mess $mess
     * @return bool
     */
    public function canAddMoreMembers(Mess $mess)
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            // Default to basic limit if no subscription
            return $mess->messUsers()->count() < 5;
        }

        $memberLimitFeature = $subscription->plan->features()
            ->where('name', 'member_limit')
            ->first();

        if (!$memberLimitFeature) {
            return true; // No limit defined
        }

        $currentMembersCount = $mess->messUsers()->count();

        return $currentMembersCount < $memberLimitFeature->usage_limit;
    }

    /**
     * Get subscription analytics for a mess.
     *
     * @param Mess $mess
     * @return array
     */
    public function getSubscriptionAnalytics(Mess $mess): array
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            return [
                'status' => 'no_subscription',
                'total_spent' => 0,
                'days_active' => 0,
                'feature_usage' => [],
                'billing_history' => [],
            ];
        }

        return [
            'status' => $subscription->status,
            'plan_name' => $subscription->plan->name,
            'package_duration' => $subscription->package->duration,
            'total_spent' => $subscription->total_spent,
            'days_active' => $subscription->starts_at->diffInDays(now()),
            'days_remaining' => max(0, $subscription->expires_at->diffInDays(now())),
            'grace_period_days' => $subscription->getTotalGracePeriodDays(),
            'feature_usage' => $this->getFeatureUsageAnalytics($subscription),
            'billing_history' => $this->getBillingHistory($subscription),
            'payment_success_rate' => $this->getPaymentSuccessRate($subscription),
            'renewal_history' => $this->getRenewalHistory($subscription),
        ];
    }

    /**
     * Get feature usage analytics for a subscription.
     *
     * @param Subscription $subscription
     * @return array
     */
    public function getFeatureUsageAnalytics(Subscription $subscription): array
    {
        $usageData = [];

        foreach ($subscription->featureUsages as $usage) {
            $feature = $usage->feature;
            $usageData[$feature->name] = [
                'used' => $usage->used_count,
                'limit' => $feature->usage_limit,
                'percentage' => $feature->usage_limit > 0 ?
                    round(($usage->used_count / $feature->usage_limit) * 100, 2) : 0,
                'is_countable' => $feature->is_countable,
                'within_limits' => $usage->withinLimits(),
                'reset_at' => $usage->reset_at,
            ];
        }

        return $usageData;
    }

    /**
     * Get billing history for a subscription.
     *
     * @param Subscription $subscription
     * @param int $limit
     * @return Collection
     */
    public function getBillingHistory(Subscription $subscription, int $limit = 10): Collection
    {
        return $subscription->orders()
            ->with(['transaction', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                    'transaction_reference' => $order->transaction->transaction_reference ?? null,
                    'invoice_number' => $order->invoice->invoice_number ?? null,
                    'notes' => $order->notes,
                ];
            });
    }

    /**
     * Get payment success rate for a subscription.
     *
     * @param Subscription $subscription
     * @return float
     */
    public function getPaymentSuccessRate(Subscription $subscription): float
    {
        $totalTransactions = $subscription->transactions()->count();

        if ($totalTransactions === 0) {
            return 100.0;
        }

        $successfulTransactions = $subscription->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->count();

        return round(($successfulTransactions / $totalTransactions) * 100, 2);
    }

    /**
     * Get renewal history for a subscription.
     *
     * @param Subscription $subscription
     * @return Collection
     */
    public function getRenewalHistory(Subscription $subscription): Collection
    {
        return $subscription->orders()
            ->where('notes', 'like', '%renewal%')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'renewed_at' => $order->created_at,
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'order_number' => $order->order_number,
                ];
            });
    }

    /**
     * Get subscription recommendations for a mess.
     *
     * @param Mess $mess
     * @return array
     */
    public function getSubscriptionRecommendations(Mess $mess): array
    {
        $subscription = $mess->activeSubscription;
        $recommendations = [];

        if (!$subscription) {
            return [
                'type' => 'start_subscription',
                'message' => 'Start with our Basic plan to unlock premium features',
                'recommended_plan' => 'basic',
                'benefits' => ['Up to 10 members', '5 monthly reports', 'Meal notifications'],
            ];
        }

        // Analyze feature usage to recommend upgrades
        $featureUsage = $this->getFeatureUsageAnalytics($subscription);

        // Check if approaching limits
        foreach ($featureUsage as $featureName => $usage) {
            if ($usage['is_countable'] && $usage['percentage'] > 80) {
                $recommendations[] = [
                    'type' => 'upgrade_warning',
                    'feature' => $featureName,
                    'message' => "You're using {$usage['percentage']}% of your {$featureName} limit",
                    'suggestion' => 'Consider upgrading to get more capacity',
                ];
            }
        }

        // Check for plan upgrade opportunities
        if ($subscription->plan->keyword === 'basic') {
            $memberCount = $mess->messUsers()->count();
            if ($memberCount > 7) {
                $recommendations[] = [
                    'type' => 'plan_upgrade',
                    'message' => 'Upgrade to Premium for 20 member limit and advanced features',
                    'recommended_plan' => 'premium',
                    'benefits' => ['20 member limit', '10 monthly reports', 'Balance notifications'],
                ];
            }
        }

        // Check billing cycle optimization
        if ($subscription->billing_cycle === 'monthly') {
            $recommendations[] = [
                'type' => 'billing_optimization',
                'message' => 'Save 20% by switching to annual billing',
                'savings' => $this->calculateAnnualSavings($subscription),
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate potential annual savings.
     *
     * @param Subscription $subscription
     * @return array
     */
    public function calculateAnnualSavings(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $monthlyPackage = $plan->packages()->where('duration', '<=', 31)->first();
        $annualPackage = $plan->packages()->where('duration', '>=', 365)->first();

        if (!$monthlyPackage || !$annualPackage) {
            return ['savings' => 0, 'percentage' => 0];
        }

        $annualCostMonthly = ($monthlyPackage->price * 12);
        $annualCostYearly = $annualPackage->price;
        $savings = $annualCostMonthly - $annualCostYearly;
        $percentage = round(($savings / $annualCostMonthly) * 100, 1);

        return [
            'monthly_cost_annual' => $annualCostMonthly,
            'yearly_cost' => $annualCostYearly,
            'savings' => $savings,
            'percentage' => $percentage,
        ];
    }

    /**
     * Generate subscription invoice with custom details.
     *
     * @param Subscription $subscription
     * @param array $customData
     * @return Invoice
     */
    public function generateCustomInvoice(Subscription $subscription, array $customData = []): Invoice
    {
        $latestOrder = $subscription->latestOrder;

        $invoiceData = array_merge([
            'mess_id' => $subscription->mess_id,
            'order_id' => $latestOrder->id ?? null,
            'transaction_id' => $subscription->last_transaction_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $subscription->package->price,
            'currency' => 'USD',
            'tax_amount' => 0,
            'total_amount' => $subscription->package->price,
            'due_date' => Carbon::now()->addDays(7),
            'status' => Invoice::STATUS_PENDING,
        ], $customData);

        return $subscription->invoices()->create($invoiceData);
    }

    /**
     * Generate unique invoice number.
     *
     * @return string
     */
    protected function generateInvoiceNumber(): string
    {
        $date = date('Ymd');
        $lastInvoice = Invoice::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ?
            intval(substr($lastInvoice->invoice_number, -5)) + 1 : 1;

        return 'INV-' . $date . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Process failed payment with retry logic.
     *
     * @param Subscription $subscription
     * @param array $paymentData
     * @param int $retryAttempts
     * @return array
     */
    public function processFailedPaymentRetry(Subscription $subscription, array $paymentData, int $retryAttempts = 3): array
    {
        $results = [];

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $success = $this->applyPayment($subscription, $paymentData);

                if ($success) {
                    $results[] = [
                        'attempt' => $attempt,
                        'status' => 'success',
                        'message' => 'Payment processed successfully',
                        'timestamp' => now(),
                    ];
                    break;
                } else {
                    $results[] = [
                        'attempt' => $attempt,
                        'status' => 'failed',
                        'message' => 'Payment processing failed',
                        'timestamp' => now(),
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'attempt' => $attempt,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'timestamp' => now(),
                ];
            }

            // Wait before next attempt (exponential backoff)
            if ($attempt < $retryAttempts) {
                sleep(pow(2, $attempt));
            }
        }

        return $results;
    }

    /**
     * Get subscription health score.
     *
     * @param Subscription $subscription
     * @return array
     */
    public function getSubscriptionHealthScore(Subscription $subscription): array
    {
        $score = 100;
        $factors = [];

        // Payment history factor (30%)
        $paymentSuccessRate = $this->getPaymentSuccessRate($subscription);
        $paymentScore = $paymentSuccessRate * 0.3;
        $score = min($score, $paymentScore + 70);
        $factors['payment_reliability'] = [
            'score' => $paymentSuccessRate,
            'weight' => 30,
            'status' => $paymentSuccessRate > 90 ? 'excellent' : ($paymentSuccessRate > 70 ? 'good' : 'poor')
        ];

        // Feature utilization factor (25%)
        $featureUsage = $this->getFeatureUsageAnalytics($subscription);
        $avgUtilization = collect($featureUsage)
            ->where('is_countable', true)
            ->avg('percentage');
        $utilizationScore = min(100, max(0, $avgUtilization));
        $factors['feature_utilization'] = [
            'score' => $utilizationScore,
            'weight' => 25,
            'status' => $utilizationScore > 60 ? 'optimal' : ($utilizationScore > 30 ? 'moderate' : 'low')
        ];

        // Subscription age factor (20%)
        $daysActive = $subscription->starts_at->diffInDays(now());
        $ageScore = min(100, ($daysActive / 365) * 100);
        $factors['subscription_maturity'] = [
            'score' => $ageScore,
            'weight' => 20,
            'status' => $ageScore > 90 ? 'mature' : ($ageScore > 30 ? 'established' : 'new')
        ];

        // Grace period usage factor (15%)
        $graceUsage = $subscription->inGracePeriod() ? 50 : 100;
        $factors['payment_timeliness'] = [
            'score' => $graceUsage,
            'weight' => 15,
            'status' => $graceUsage == 100 ? 'timely' : 'delayed'
        ];

        // Cancellation risk factor (10%)
        $cancellationRisk = $subscription->is_canceled ? 0 : 100;
        $factors['retention_status'] = [
            'score' => $cancellationRisk,
            'weight' => 10,
            'status' => $cancellationRisk == 100 ? 'active' : 'at_risk'
        ];

        $overallScore = collect($factors)->sum(function ($factor) {
            return ($factor['score'] * $factor['weight']) / 100;
        });

        return [
            'overall_score' => round($overallScore, 1),
            'grade' => $this->getHealthGrade($overallScore),
            'factors' => $factors,
            'recommendations' => $this->getHealthRecommendations($factors),
        ];
    }

    /**
     * Get health grade based on score.
     *
     * @param float $score
     * @return string
     */
    protected function getHealthGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Get health recommendations based on factors.
     *
     * @param array $factors
     * @return array
     */
    protected function getHealthRecommendations(array $factors): array
    {
        $recommendations = [];

        if ($factors['payment_reliability']['score'] < 80) {
            $recommendations[] = 'Set up automatic payments to improve payment reliability';
        }

        if ($factors['feature_utilization']['score'] < 30) {
            $recommendations[] = 'Explore available features to maximize your subscription value';
        }

        if ($factors['payment_timeliness']['status'] === 'delayed') {
            $recommendations[] = 'Update payment method to avoid service interruptions';
        }

        return $recommendations;
    }

    /**
     * Get subscription comparison between plans.
     *
     * @param string $currentPlanKeyword
     * @param string $targetPlanKeyword
     * @return array
     */
    public function compareSubscriptionPlans(string $currentPlanKeyword, string $targetPlanKeyword): array
    {
        $currentPlan = Plan::where('keyword', $currentPlanKeyword)->with('features', 'packages')->first();
        $targetPlan = Plan::where('keyword', $targetPlanKeyword)->with('features', 'packages')->first();

        if (!$currentPlan || !$targetPlan) {
            throw new \InvalidArgumentException('Invalid plan keywords provided');
        }

        $comparison = [
            'current_plan' => $this->formatPlanForComparison($currentPlan),
            'target_plan' => $this->formatPlanForComparison($targetPlan),
            'feature_differences' => $this->compareFeatures($currentPlan, $targetPlan),
            'pricing_differences' => $this->comparePricing($currentPlan, $targetPlan),
            'upgrade_benefits' => $this->getUpgradeBenefits($currentPlan, $targetPlan),
        ];

        return $comparison;
    }

    /**
     * Format plan data for comparison.
     *
     * @param Plan $plan
     * @return array
     */
    protected function formatPlanForComparison(Plan $plan): array
    {
        return [
            'name' => $plan->name,
            'keyword' => $plan->keyword,
            'is_free' => $plan->is_free,
            'features' => $plan->features->map(function ($feature) {
                return [
                    'name' => $feature->name,
                    'is_countable' => $feature->is_countable,
                    'limit' => $feature->usage_limit,
                ];
            })->toArray(),
            'packages' => $plan->packages->map(function ($package) {
                return [
                    'duration' => $package->duration,
                    'price' => $package->price,
                    'is_trial' => $package->is_trial,
                ];
            })->toArray(),
        ];
    }

    /**
     * Compare features between plans.
     *
     * @param Plan $currentPlan
     * @param Plan $targetPlan
     * @return array
     */
    protected function compareFeatures(Plan $currentPlan, Plan $targetPlan): array
    {
        $currentFeatures = $currentPlan->features->keyBy('name');
        $targetFeatures = $targetPlan->features->keyBy('name');

        $differences = [];

        // Check for new features in target plan
        foreach ($targetFeatures as $featureName => $feature) {
            if (!$currentFeatures->has($featureName)) {
                $differences['new_features'][] = [
                    'name' => $featureName,
                    'limit' => $feature->usage_limit,
                    'is_countable' => $feature->is_countable,
                ];
            } elseif ($feature->usage_limit > $currentFeatures[$featureName]->usage_limit) {
                $differences['improved_limits'][] = [
                    'name' => $featureName,
                    'current_limit' => $currentFeatures[$featureName]->usage_limit,
                    'new_limit' => $feature->usage_limit,
                    'improvement' => $feature->usage_limit - $currentFeatures[$featureName]->usage_limit,
                ];
            }
        }

        // Check for removed features
        foreach ($currentFeatures as $featureName => $feature) {
            if (!$targetFeatures->has($featureName)) {
                $differences['removed_features'][] = [
                    'name' => $featureName,
                    'current_limit' => $feature->usage_limit,
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare pricing between plans.
     *
     * @param Plan $currentPlan
     * @param Plan $targetPlan
     * @return array
     */
    protected function comparePricing(Plan $currentPlan, Plan $targetPlan): array
    {
        $currentPackages = $currentPlan->packages->keyBy('duration');
        $targetPackages = $targetPlan->packages->keyBy('duration');

        $pricingComparison = [];

        foreach ($targetPackages as $duration => $targetPackage) {
            $currentPackage = $currentPackages->get($duration);

            if ($currentPackage) {
                $difference = $targetPackage->price - $currentPackage->price;
                $pricingComparison[$duration . '_days'] = [
                    'current_price' => $currentPackage->price,
                    'target_price' => $targetPackage->price,
                    'difference' => $difference,
                    'percentage_change' => $currentPackage->price > 0 ?
                        round(($difference / $currentPackage->price) * 100, 1) : 0,
                ];
            }
        }

        return $pricingComparison;
    }

    /**
     * Get upgrade benefits summary.
     *
     * @param Plan $currentPlan
     * @param Plan $targetPlan
     * @return array
     */
    protected function getUpgradeBenefits(Plan $currentPlan, Plan $targetPlan): array
    {
        $featureDiff = $this->compareFeatures($currentPlan, $targetPlan);

        $benefits = [];

        if (isset($featureDiff['new_features'])) {
            $benefits['new_capabilities'] = count($featureDiff['new_features']) . ' new features unlocked';
        }

        if (isset($featureDiff['improved_limits'])) {
            $benefits['enhanced_limits'] = count($featureDiff['improved_limits']) . ' features with higher limits';
        }

        // Calculate value proposition
        $monthlyPackageCurrent = $currentPlan->packages()->where('duration', '<=', 31)->first();
        $monthlyPackageTarget = $targetPlan->packages()->where('duration', '<=', 31)->first();

        if ($monthlyPackageCurrent && $monthlyPackageTarget) {
            $costIncrease = $monthlyPackageTarget->price - $monthlyPackageCurrent->price;
            $benefits['cost_analysis'] = [
                'monthly_increase' => $costIncrease,
                'cost_per_new_feature' => isset($featureDiff['new_features']) ?
                    round($costIncrease / count($featureDiff['new_features']), 2) : 0,
            ];
        }

        return $benefits;
    }

    /**
     * Bulk update subscriptions based on criteria.
     *
     * @param array $criteria
     * @param array $updates
     * @return array
     */
    public function bulkUpdateSubscriptions(array $criteria, array $updates): array
    {
        $query = Subscription::query();

        // Apply criteria filters
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $subscriptions = $query->get();
        $results = ['updated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($subscriptions as $subscription) {
            try {
                $subscription->update($updates);
                $results['updated']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate subscription performance report.
     *
     * @param array $filters
     * @return array
     */
    public function generatePerformanceReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        return Cache::remember(
            "subscription_report_{$dateFrom}_{$dateTo}",
            3600,
            function () use ($dateFrom, $dateTo) {
                return [
                    'overview' => $this->getSubscriptionOverview($dateFrom, $dateTo),
                    'revenue' => $this->getRevenueMetrics($dateFrom, $dateTo),
                    'churn' => $this->getChurnMetrics($dateFrom, $dateTo),
                    'growth' => $this->getGrowthMetrics($dateFrom, $dateTo),
                    'feature_adoption' => $this->getFeatureAdoptionMetrics($dateFrom, $dateTo),
                ];
            }
        );
    }

    /**
     * Get subscription overview metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getSubscriptionOverview(Carbon $dateFrom, Carbon $dateTo): array
    {
        return [
            'total_subscriptions' => Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_subscriptions' => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            'trial_subscriptions' => Subscription::where('status', Subscription::STATUS_TRIAL)->count(),
            'grace_period_subscriptions' => Subscription::where('status', Subscription::STATUS_GRACE_PERIOD)->count(),
            'canceled_subscriptions' => Subscription::where('is_canceled', true)->count(),
        ];
    }

    /**
     * Get revenue metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getRevenueMetrics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $transactions = Transaction::where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$dateFrom, $dateTo]);

        return [
            'total_revenue' => $transactions->sum('amount'),
            'average_transaction' => $transactions->avg('amount'),
            'transaction_count' => $transactions->count(),
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateARR(),
        ];
    }

    /**
     * Calculate Monthly Recurring Revenue.
     *
     * @return float
     */
    protected function calculateMRR(): float
    {
        return Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->with('package')
            ->get()
            ->sum(function ($subscription) {
                $package = $subscription->package;
                return ($package->price / $package->duration) * 30; // Convert to monthly
            });
    }

    /**
     * Calculate Annual Recurring Revenue.
     *
     * @return float
     */
    protected function calculateARR(): float
    {
        return $this->calculateMRR() * 12;
    }

    /**
     * Get churn metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getChurnMetrics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $canceledCount = Subscription::where('is_canceled', true)
            ->whereBetween('canceled_at', [$dateFrom, $dateTo])
            ->count();

        $totalSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        return [
            'canceled_subscriptions' => $canceledCount,
            'churn_rate' => $totalSubscriptions > 0 ?
                round(($canceledCount / $totalSubscriptions) * 100, 2) : 0,
            'retention_rate' => $totalSubscriptions > 0 ?
                round((($totalSubscriptions - $canceledCount) / $totalSubscriptions) * 100, 2) : 100,
        ];
    }

    /**
     * Get growth metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getGrowthMetrics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $newSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $previousPeriodFrom = $dateFrom->copy()->subDays($dateTo->diffInDays($dateFrom));
        $previousNewSubscriptions = Subscription::whereBetween('created_at', [$previousPeriodFrom, $dateFrom])->count();

        $growthRate = $previousNewSubscriptions > 0 ?
            round((($newSubscriptions - $previousNewSubscriptions) / $previousNewSubscriptions) * 100, 2) : 0;

        return [
            'new_subscriptions' => $newSubscriptions,
            'previous_period_subscriptions' => $previousNewSubscriptions,
            'growth_rate' => $growthRate,
        ];
    }

    /**
     * Get feature adoption metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getFeatureAdoptionMetrics(Carbon $dateFrom, Carbon $dateTo): array
    {
        $featureUsages = FeatureUsage::with('feature')
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->where('used_count', '>', 0)
            ->get()
            ->groupBy('feature.name');

        $adoptionMetrics = [];

        foreach ($featureUsages as $featureName => $usages) {
            $adoptionMetrics[$featureName] = [
                'total_usage' => $usages->sum('used_count'),
                'active_users' => $usages->count(),
                'average_usage_per_user' => round($usages->avg('used_count'), 2),
            ];
        }

        return $adoptionMetrics;
    }

    /**
     * Get subscription lifecycle events for a mess.
     *
     * @param Mess $mess
     * @param int $limit
     * @return Collection
     */
    public function getSubscriptionLifecycleEvents(Mess $mess, int $limit = 20): Collection
    {
        $subscription = $mess->activeSubscription;

        if (!$subscription) {
            return collect([]);
        }

        return $subscription->orders()
            ->with(['transaction', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $eventType = $this->determineEventType($order);

                return [
                    'event_type' => $eventType,
                    'event_date' => $order->created_at,
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'description' => $this->getEventDescription($eventType, $order),
                    'metadata' => [
                        'transaction_reference' => $order->transaction->transaction_reference ?? null,
                        'invoice_number' => $order->invoice->invoice_number ?? null,
                        'payment_method' => $order->transaction->payment_method ?? null,
                    ],
                ];
            });
    }

    /**
     * Determine event type from order.
     *
     * @param SubscriptionOrder $order
     * @return string
     */
    protected function determineEventType(SubscriptionOrder $order): string
    {
        $notes = strtolower($order->notes ?? '');

        if (str_contains($notes, 'renewal')) return 'renewal';
        if (str_contains($notes, 'grace period')) return 'grace_period_entry';
        if (str_contains($notes, 'plan change')) return 'plan_change';
        if (str_contains($notes, 'cancel')) return 'cancellation';
        if (str_contains($notes, 'expired')) return 'expiration';
        if (str_contains($notes, 'admin')) return 'admin_action';
        if ($order->amount == 0) return 'system_event';

        return 'subscription_start';
    }

    /**
     * Get event description.
     *
     * @param string $eventType
     * @param SubscriptionOrder $order
     * @return string
     */
    protected function getEventDescription(string $eventType, SubscriptionOrder $order): string
    {
        return match($eventType) {
            'renewal' => 'Subscription renewed for ' . $order->subscription->package->duration . ' days',
            'grace_period_entry' => 'Subscription entered grace period due to payment issues',
            'plan_change' => 'Subscription plan changed to ' . $order->plan->name,
            'cancellation' => 'Subscription was cancelled',
            'expiration' => 'Subscription expired after grace period',
            'admin_action' => 'Admin performed subscription action',
            'system_event' => 'System generated event',
            default => 'New subscription created',
        };
    }

    /**
     * Generate dunning management report for overdue subscriptions.
     *
     * @param array $filters
     * @return array
     */
    public function generateDunningReport(array $filters = []): array
    {
        $daysOverdue = $filters['days_overdue'] ?? 7;
        $gracePeriodOnly = $filters['grace_period_only'] ?? false;

        $query = Subscription::with(['mess', 'plan', 'package', 'latestOrder', 'latestTransaction'])
            ->where('payment_status', '!=', Subscription::PAYMENT_STATUS_PAID)
            ->where('expires_at', '<', Carbon::now()->subDays($daysOverdue));

        if ($gracePeriodOnly) {
            $query->where('status', Subscription::STATUS_GRACE_PERIOD);
        }

        $overdueSubscriptions = $query->get();

        return [
            'summary' => [
                'total_overdue' => $overdueSubscriptions->count(),
                'total_amount_due' => $overdueSubscriptions->sum('package.price'),
                'average_days_overdue' => $overdueSubscriptions->avg(function ($sub) {
                    return $sub->expires_at->diffInDays(now());
                }),
                'grace_period_count' => $overdueSubscriptions->where('status', Subscription::STATUS_GRACE_PERIOD)->count(),
            ],
            'subscriptions_by_age' => $this->groupSubscriptionsByAge($overdueSubscriptions),
            'subscriptions_by_plan' => $this->groupSubscriptionsByPlan($overdueSubscriptions),
            'recommended_actions' => $this->getRecommendedDunningActions($overdueSubscriptions),
            'detailed_list' => $overdueSubscriptions->map(function ($subscription) {
                return [
                    'subscription_id' => $subscription->id,
                    'mess_name' => $subscription->mess->name,
                    'plan' => $subscription->plan->name,
                    'amount_due' => $subscription->package->price,
                    'days_overdue' => $subscription->expires_at->diffInDays(now()),
                    'status' => $subscription->status,
                    'grace_period_ends' => $subscription->grace_period_ends_at,
                    'last_payment_attempt' => $subscription->latestTransaction->processed_at ?? null,
                    'contact_email' => $subscription->latestOrder->billing_email ?? null,
                ];
            })->toArray(),
        ];
    }

    /**
     * Group subscriptions by overdue age.
     *
     * @param Collection $subscriptions
     * @return array
     */
    protected function groupSubscriptionsByAge(Collection $subscriptions): array
    {
        return $subscriptions->groupBy(function ($subscription) {
            $daysOverdue = $subscription->expires_at->diffInDays(now());

            if ($daysOverdue <= 7) return '1-7 days';
            if ($daysOverdue <= 14) return '8-14 days';
            if ($daysOverdue <= 30) return '15-30 days';
            return '30+ days';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('package.price'),
            ];
        })->toArray();
    }

    /**
     * Group subscriptions by plan.
     *
     * @param Collection $subscriptions
     * @return array
     */
    protected function groupSubscriptionsByPlan(Collection $subscriptions): array
    {
        return $subscriptions->groupBy('plan.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('package.price'),
                'average_days_overdue' => $group->avg(function ($sub) {
                    return $sub->expires_at->diffInDays(now());
                }),
            ];
        })->toArray();
    }

    /**
     * Get recommended dunning actions.
     *
     * @param Collection $subscriptions
     * @return array
     */
    protected function getRecommendedDunningActions(Collection $subscriptions): array
    {
        $actions = [];

        $recentlyOverdue = $subscriptions->filter(function ($sub) {
            return $sub->expires_at->diffInDays(now()) <= 3;
        });

        $moderatelyOverdue = $subscriptions->filter(function ($sub) {
            $days = $sub->expires_at->diffInDays(now());
            return $days > 3 && $days <= 14;
        });

        $severelyOverdue = $subscriptions->filter(function ($sub) {
            return $sub->expires_at->diffInDays(now()) > 14;
        });

        if ($recentlyOverdue->count() > 0) {
            $actions[] = [
                'priority' => 'low',
                'action' => 'send_payment_reminder',
                'count' => $recentlyOverdue->count(),
                'description' => 'Send gentle payment reminder emails',
            ];
        }

        if ($moderatelyOverdue->count() > 0) {
            $actions[] = [
                'priority' => 'medium',
                'action' => 'extend_grace_period',
                'count' => $moderatelyOverdue->count(),
                'description' => 'Consider extending grace period and send urgent payment notice',
            ];
        }

        if ($severelyOverdue->count() > 0) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'suspend_services',
                'count' => $severelyOverdue->count(),
                'description' => 'Suspend services and initiate collection process',
            ];
        }

        return $actions;
    }

    /**
     * Process subscription auto-renewal with retry logic.
     *
     * @param Subscription $subscription
     * @param int $maxRetries
     * @return array
     */
    public function processAutoRenewal(Subscription $subscription, int $maxRetries = 3): array
    {
        if (!$this->shouldAutoRenew($subscription)) {
            return [
                'success' => false,
                'reason' => 'Subscription not eligible for auto-renewal',
                'subscription_status' => $subscription->status,
            ];
        }

        $paymentData = [
            'payment_method' => $subscription->payment_method,
            'payment_id' => $subscription->payment_id,
        ];

        $renewalResults = [];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $renewedSubscription = $this->renew($subscription, $paymentData);

                return [
                    'success' => true,
                    'subscription_id' => $renewedSubscription->id,
                    'attempts' => $attempt,
                    'new_expires_at' => $renewedSubscription->expires_at,
                    'renewal_history' => $renewalResults,
                ];
            } catch (\Exception $e) {
                $renewalResults[] = [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'timestamp' => now(),
                ];

                if ($attempt === $maxRetries) {
                    // Enter grace period on final failure
                    $this->enterGracePeriod($subscription);

                    return [
                        'success' => false,
                        'reason' => 'Auto-renewal failed after ' . $maxRetries . ' attempts',
                        'entered_grace_period' => true,
                        'renewal_history' => $renewalResults,
                    ];
                }

                // Wait before retry (exponential backoff)
                sleep(pow(2, $attempt));
            }
        }

        return [
            'success' => false,
            'reason' => 'Unexpected error in auto-renewal process',
            'renewal_history' => $renewalResults,
        ];
    }

    /**
     * Check if subscription should auto-renew.
     *
     * @param Subscription $subscription
     * @return bool
     */
    protected function shouldAutoRenew(Subscription $subscription): bool
    {
        return !$subscription->is_canceled &&
               $subscription->status === Subscription::STATUS_ACTIVE &&
               $subscription->payment_method &&
               $subscription->expires_at->subDays(1)->isPast();
    }

    /**
     * Generate subscription metrics for business intelligence.
     *
     * @param array $filters
     * @return array
     */
    public function generateBusinessMetrics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subMonths(12);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        return [
            'customer_metrics' => [
                'customer_acquisition_cost' => $this->calculateCustomerAcquisitionCost($dateFrom, $dateTo),
                'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
                'average_revenue_per_user' => $this->calculateAverageRevenuePerUser($dateFrom, $dateTo),
                'net_promoter_score' => $this->calculateNetPromoterScore(), // Would need survey data
            ],
            'subscription_metrics' => [
                'mrr_growth_rate' => $this->calculateMRRGrowthRate($dateFrom, $dateTo),
                'churn_by_plan' => $this->getChurnByPlan($dateFrom, $dateTo),
                'upgrade_downgrade_rates' => $this->getUpgradeDowngradeRates($dateFrom, $dateTo),
                'trial_conversion_rate' => $this->getTrialConversionRate($dateFrom, $dateTo),
            ],
            'financial_metrics' => [
                'recurring_revenue_breakdown' => $this->getRecurringRevenueBreakdown(),
                'payment_method_performance' => $this->getPaymentMethodPerformance($dateFrom, $dateTo),
                'refund_and_chargeback_rates' => $this->getRefundChargebackRates($dateFrom, $dateTo),
            ],
            'operational_metrics' => [
                'grace_period_recovery_rate' => $this->getGracePeriodRecoveryRate($dateFrom, $dateTo),
                'dunning_effectiveness' => $this->getDunningEffectiveness($dateFrom, $dateTo),
                'support_ticket_correlation' => $this->getSupportTicketCorrelation($dateFrom, $dateTo),
            ],
        ];
    }

    /**
     * Calculate Customer Acquisition Cost.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return float
     */
    protected function calculateCustomerAcquisitionCost(Carbon $dateFrom, Carbon $dateTo): float
    {
        $newSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        // This would typically include marketing spend, sales costs, etc.
        // For now, we'll use a simplified calculation based on system costs
        $estimatedAcquisitionCosts = $newSubscriptions * 25; // $25 per acquisition

        return $newSubscriptions > 0 ? $estimatedAcquisitionCosts / $newSubscriptions : 0;
    }

    /**
     * Calculate Customer Lifetime Value.
     *
     * @return float
     */
    protected function calculateCustomerLifetimeValue(): float
    {
        $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)->with('package')->get();

        if ($activeSubscriptions->isEmpty()) {
            return 0;
        }

        $averageMonthlyRevenue = $activeSubscriptions->avg(function ($subscription) {
            return ($subscription->package->price / $subscription->package->duration) * 30;
        });

        $averageLifespanMonths = $activeSubscriptions->avg(function ($subscription) {
            return $subscription->created_at->diffInMonths(now());
        });

        return $averageMonthlyRevenue * $averageLifespanMonths;
    }

    /**
     * Calculate Average Revenue Per User.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return float
     */
    protected function calculateAverageRevenuePerUser(Carbon $dateFrom, Carbon $dateTo): float
    {
        $totalRevenue = Transaction::where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $uniqueUsers = Transaction::where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->distinct('mess_id')
            ->count();

        return $uniqueUsers > 0 ? $totalRevenue / $uniqueUsers : 0;
    }

    /**
     * Calculate Net Promoter Score placeholder.
     *
     * @return float
     */
    protected function calculateNetPromoterScore(): float
    {
        // This would require customer survey data
        // Returning a placeholder value
        return 7.5; // Simulated NPS score
    }

    /**
     * Calculate MRR growth rate.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return float
     */
    protected function calculateMRRGrowthRate(Carbon $dateFrom, Carbon $dateTo): float
    {
        $currentMRR = $this->calculateMRR();

        // Calculate MRR for the start of the period
        $previousPeriodSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('created_at', '<=', $dateFrom)
            ->with('package')
            ->get();

        $previousMRR = $previousPeriodSubscriptions->sum(function ($subscription) {
            return ($subscription->package->price / $subscription->package->duration) * 30;
        });

        return $previousMRR > 0 ? (($currentMRR - $previousMRR) / $previousMRR) * 100 : 0;
    }

    /**
     * Get churn rate by plan.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getChurnByPlan(Carbon $dateFrom, Carbon $dateTo): array
    {
        $churnByPlan = [];

        $plans = Plan::with(['subscriptions' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])->get();

        foreach ($plans as $plan) {
            $totalSubscriptions = $plan->subscriptions->count();
            $canceledSubscriptions = $plan->subscriptions->where('is_canceled', true)->count();

            $churnByPlan[$plan->name] = [
                'total_subscriptions' => $totalSubscriptions,
                'canceled_subscriptions' => $canceledSubscriptions,
                'churn_rate' => $totalSubscriptions > 0 ?
                    round(($canceledSubscriptions / $totalSubscriptions) * 100, 2) : 0,
            ];
        }

        return $churnByPlan;
    }

    /**
     * Get upgrade/downgrade rates.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getUpgradeDowngradeRates(Carbon $dateFrom, Carbon $dateTo): array
    {
        $planChanges = SubscriptionOrder::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('notes', 'like', '%plan change%')
            ->with(['subscription', 'plan'])
            ->get();

        $upgrades = 0;
        $downgrades = 0;

        foreach ($planChanges as $order) {
            // This would require more sophisticated logic to determine upgrade vs downgrade
            // For now, using a simple heuristic based on price
            $currentPackagePrice = $order->subscription->package->price ?? 0;

            if ($order->amount > $currentPackagePrice) {
                $upgrades++;
            } else {
                $downgrades++;
            }
        }

        $totalChanges = $upgrades + $downgrades;

        return [
            'total_plan_changes' => $totalChanges,
            'upgrades' => $upgrades,
            'downgrades' => $downgrades,
            'upgrade_rate' => $totalChanges > 0 ? round(($upgrades / $totalChanges) * 100, 2) : 0,
            'downgrade_rate' => $totalChanges > 0 ? round(($downgrades / $totalChanges) * 100, 2) : 0,
        ];
    }

    /**
     * Get trial conversion rate.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getTrialConversionRate(Carbon $dateFrom, Carbon $dateTo): array
    {
        $trialSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('trial_ends_at')
            ->get();

        $convertedTrials = $trialSubscriptions->filter(function ($subscription) {
            return $subscription->transactions()
                ->where('status', Transaction::STATUS_COMPLETED)
                ->where('amount', '>', 0)
                ->exists();
        });

        $totalTrials = $trialSubscriptions->count();
        $conversions = $convertedTrials->count();

        return [
            'total_trials' => $totalTrials,
            'converted_trials' => $conversions,
            'conversion_rate' => $totalTrials > 0 ? round(($conversions / $totalTrials) * 100, 2) : 0,
            'average_trial_duration' => $trialSubscriptions->avg(function ($sub) {
                return $sub->starts_at->diffInDays($sub->trial_ends_at);
            }),
        ];
    }

    /**
     * Get recurring revenue breakdown.
     *
     * @return array
     */
    protected function getRecurringRevenueBreakdown(): array
    {
        $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->with(['plan', 'package'])
            ->get();

        return $activeSubscriptions->groupBy('plan.name')->map(function ($subscriptions, $planName) {
            $monthlyRevenue = $subscriptions->sum(function ($subscription) {
                return ($subscription->package->price / $subscription->package->duration) * 30;
            });

            return [
                'subscription_count' => $subscriptions->count(),
                'monthly_revenue' => $monthlyRevenue,
                'annual_revenue' => $monthlyRevenue * 12,
                'average_revenue_per_subscription' => $subscriptions->count() > 0 ?
                    $monthlyRevenue / $subscriptions->count() : 0,
            ];
        })->toArray();
    }

    /**
     * Get payment method performance.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getPaymentMethodPerformance(Carbon $dateFrom, Carbon $dateTo): array
    {
        $transactions = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        return $transactions->groupBy('payment_method')->map(function ($methodTransactions, $method) {
            $total = $methodTransactions->count();
            $successful = $methodTransactions->where('status', Transaction::STATUS_COMPLETED)->count();
            $failed = $methodTransactions->where('status', Transaction::STATUS_FAILED)->count();

            return [
                'total_transactions' => $total,
                'successful_transactions' => $successful,
                'failed_transactions' => $failed,
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                'total_amount' => $methodTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('amount'),
            ];
        })->toArray();
    }

    /**
     * Get refund and chargeback rates.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getRefundChargebackRates(Carbon $dateFrom, Carbon $dateTo): array
    {
        $totalTransactions = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $refundedTransactions = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', Transaction::STATUS_REFUNDED)
            ->count();

        return [
            'total_transactions' => $totalTransactions,
            'refunded_transactions' => $refundedTransactions,
            'refund_rate' => $totalTransactions > 0 ?
                round(($refundedTransactions / $totalTransactions) * 100, 2) : 0,
            'chargeback_rate' => 0, // Would need integration with payment processor
        ];
    }

    /**
     * Get grace period recovery rate.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getGracePeriodRecoveryRate(Carbon $dateFrom, Carbon $dateTo): array
    {
        $gracePeriodEntries = SubscriptionOrder::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('notes', 'like', '%grace period%')
            ->pluck('subscription_id');

        $totalGracePeriods = $gracePeriodEntries->count();

        $recoveredSubscriptions = Subscription::whereIn('id', $gracePeriodEntries)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count();

        return [
            'total_grace_periods' => $totalGracePeriods,
            'recovered_subscriptions' => $recoveredSubscriptions,
            'recovery_rate' => $totalGracePeriods > 0 ?
                round(($recoveredSubscriptions / $totalGracePeriods) * 100, 2) : 0,
        ];
    }

    /**
     * Get dunning effectiveness metrics.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getDunningEffectiveness(Carbon $dateFrom, Carbon $dateTo): array
    {
        // This would track dunning campaign effectiveness
        // Placeholder implementation
        return [
            'campaigns_sent' => 150,
            'payments_recovered' => 45,
            'effectiveness_rate' => 30.0,
            'average_recovery_time_days' => 5.2,
        ];
    }

    /**
     * Get support ticket correlation.
     *
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    protected function getSupportTicketCorrelation(Carbon $dateFrom, Carbon $dateTo): array
    {
        // This would correlate support tickets with subscription issues
        // Placeholder implementation
        return [
            'payment_related_tickets' => 25,
            'plan_change_tickets' => 15,
            'feature_access_tickets' => 10,
            'billing_inquiry_tickets' => 30,
        ];
    }

    /**
     * Create subscription snapshot for historical tracking.
     *
     * @param Subscription $subscription
     * @param string $reason
     * @return array
     */
    public function createSubscriptionSnapshot(Subscription $subscription, string $reason = 'manual'): array
    {
        $snapshot = [
            'subscription_id' => $subscription->id,
            'snapshot_date' => now(),
            'reason' => $reason,
            'subscription_data' => [
                'status' => $subscription->status,
                'plan_name' => $subscription->plan->name,
                'package_duration' => $subscription->package->duration,
                'package_price' => $subscription->package->price,
                'starts_at' => $subscription->starts_at,
                'expires_at' => $subscription->expires_at,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
                'total_spent' => $subscription->total_spent,
                'payment_status' => $subscription->payment_status,
                'is_canceled' => $subscription->is_canceled,
            ],
            'feature_usage' => $this->getFeatureUsageAnalytics($subscription),
            'billing_summary' => [
                'total_orders' => $subscription->orders()->count(),
                'successful_payments' => $subscription->transactions()
                    ->where('status', Transaction::STATUS_COMPLETED)->count(),
                'failed_payments' => $subscription->transactions()
                    ->where('status', Transaction::STATUS_FAILED)->count(),
            ],
        ];

        // In a real implementation, you'd store this in a snapshots table
        Cache::put("subscription_snapshot_{$subscription->id}_" . now()->timestamp, $snapshot, 86400 * 30);

        return $snapshot;
    }

    /**
     * Generate automatic invoice for upcoming billing cycle.
     *
     * @param Subscription $subscription
     * @param int $daysInAdvance
     * @return Invoice|null
     */
    public function generateAutomaticInvoice(Subscription $subscription, int $daysInAdvance = 7): ?Invoice
    {
        if (!$this->shouldGenerateAutomaticInvoice($subscription, $daysInAdvance)) {
            return null;
        }

        $nextBillingDate = $subscription->next_billing_date ?? $subscription->expires_at;
        $newStartDate = $subscription->expires_at;
        $newEndDate = $newStartDate->copy()->addDays($subscription->package->duration);

        $taxAmount = $this->calculateTaxAmount($subscription);
        $discountAmount = $this->calculateAutomaticDiscount($subscription);
        $totalAmount = $subscription->package->price + $taxAmount - $discountAmount;

        $invoice = $subscription->invoices()->create([
            'mess_id' => $subscription->mess_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $subscription->package->price,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'billing_address' => $subscription->latestOrder->billing_address ?? null,
            'billing_email' => $subscription->latestOrder->billing_email ?? null,
            'due_date' => $nextBillingDate,
            'issued_date' => now(),
            'status' => Invoice::STATUS_PENDING,
            'notes' => "Automatic invoice for next billing cycle ({$newStartDate->format('Y-m-d')} to {$newEndDate->format('Y-m-d')})",
        ]);

        $order = $subscription->createOrder([
            'status' => SubscriptionOrder::STATUS_PENDING,
            'payment_status' => 'pending',
            'amount' => $invoice->amount,
            'tax_amount' => $invoice->tax_amount,
            'discount_amount' => $invoice->discount_amount,
            'total_amount' => $invoice->total_amount,
            'billing_email' => $invoice->billing_email,
            'billing_address' => $invoice->billing_address,
            'notes' => 'Automatic invoice generation for upcoming renewal',
        ]);

        $invoice->update(['order_id' => $order->id]);
        $this->createSubscriptionSnapshot($subscription, 'automatic_invoice_generated');

        return $invoice;
    }

    /**
     * Check if subscription should generate automatic invoice.
     *
     * @param Subscription $subscription
     * @param int $daysInAdvance
     * @return bool
     */
    protected function shouldGenerateAutomaticInvoice(Subscription $subscription, int $daysInAdvance): bool
    {
        if ($subscription->is_canceled ||
            $subscription->status === Subscription::STATUS_EXPIRED ||
            $subscription->status === Subscription::STATUS_TRIAL ||
            $subscription->plan->is_free ||
            $subscription->package->price <= 0) {
            return false;
        }

        $nextBillingDate = $subscription->next_billing_date ?? $subscription->expires_at;
        $generateDate = $nextBillingDate->copy()->subDays($daysInAdvance);

        if (now() < $generateDate) {
            return false;
        }

        return !$subscription->invoices()
            ->where('due_date', $nextBillingDate->format('Y-m-d'))
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->exists();
    }

    /**
     * Calculate tax amount for subscription.
     *
     * @param Subscription $subscription
     * @return float
     */
    protected function calculateTaxAmount(Subscription $subscription): float
    {
        $taxRate = 0.08; // 8% tax rate - would be configurable
        return round($subscription->package->price * $taxRate, 2);
    }

    /**
     * Calculate automatic discount for loyal customers.
     *
     * @param Subscription $subscription
     * @return float
     */
    protected function calculateAutomaticDiscount(Subscription $subscription): float
    {
        $subscriptionAgeMonths = $subscription->created_at->diffInMonths(now());

        if ($subscriptionAgeMonths >= 12) {
            return round($subscription->package->price * 0.10, 2); // 10% for 1+ years
        } elseif ($subscriptionAgeMonths >= 6) {
            return round($subscription->package->price * 0.05, 2); // 5% for 6+ months
        }

        return 0;
    }

    /**
     * Process automatic billing for all eligible subscriptions.
     *
     * @param int $daysInAdvance
     * @return array
     */
    public function processAutomaticBilling(int $daysInAdvance = 7): array
    {
        $targetDate = now()->addDays($daysInAdvance);

        $eligibleSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('is_canceled', false)
            ->where(function ($query) use ($targetDate) {
                $query->where('next_billing_date', '<=', $targetDate)
                      ->orWhere('expires_at', '<=', $targetDate);
            })
            ->with(['plan', 'package', 'mess', 'latestOrder'])
            ->get();

        $results = [
            'processed' => 0,
            'invoices_generated' => 0,
            'payments_attempted' => 0,
            'payments_successful' => 0,
            'payments_failed' => 0,
            'errors' => [],
        ];

        foreach ($eligibleSubscriptions as $subscription) {
            try {
                $results['processed']++;

                $invoice = $this->generateAutomaticInvoice($subscription, $daysInAdvance);

                if ($invoice) {
                    $results['invoices_generated']++;

                    if ($subscription->payment_method && $subscription->payment_id) {
                        $results['payments_attempted']++;

                        $paymentResult = $this->attemptAutomaticPayment($subscription, $invoice);

                        if ($paymentResult['success']) {
                            $results['payments_successful']++;
                        } else {
                            $results['payments_failed']++;
                            $results['errors'][] = [
                                'subscription_id' => $subscription->id,
                                'type' => 'payment_failed',
                                'message' => $paymentResult['error'] ?? 'Payment failed',
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'type' => 'processing_error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Attempt automatic payment for an invoice.
     *
     * @param Subscription $subscription
     * @param Invoice $invoice
     * @return array
     */
    protected function attemptAutomaticPayment(Subscription $subscription, Invoice $invoice): array
    {
        try {
            $paymentData = [
                'payment_method' => $subscription->payment_method,
                'payment_id' => $subscription->payment_id,
                'amount' => $invoice->total_amount,
                'billing_email' => $invoice->billing_email,
            ];

            $transaction = $subscription->recordTransaction([
                'order_id' => $invoice->order_id,
                'payment_method' => $paymentData['payment_method'],
                'amount' => $paymentData['amount'],
                'status' => Transaction::STATUS_PENDING,
                'notes' => 'Automatic payment attempt for invoice ' . $invoice->invoice_number,
            ]);

            $paymentSuccessful = $this->simulatePaymentGateway($paymentData);

            if ($paymentSuccessful) {
                $transaction->markAsComplete();

                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_date' => now(),
                ]);

                $this->renew($subscription, $paymentData);
                return ['success' => true];
            } else {
                $transaction->markAsFailed('Automatic payment failed');
                return [
                    'success' => false,
                    'error' => 'Payment gateway declined the transaction',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Simulate payment gateway processing.
     *
     * @param array $paymentData
     * @return bool
     */
    protected function simulatePaymentGateway(array $paymentData): bool
    {
        // Simulate payment success 85% of the time
        return rand(1, 100) <= 85;
    }

    /**
     * Send invoice reminder notifications.
     *
     * @param array $filters
     * @return array
     */
    public function sendInvoiceReminders(array $filters = []): array
    {
        $daysBeforeDue = $filters['days_before_due'] ?? [7, 3, 1];
        $results = [
            'reminders_sent' => 0,
            'errors' => [],
        ];

        foreach ($daysBeforeDue as $days) {
            $targetDate = now()->addDays($days)->format('Y-m-d');

            $pendingInvoices = Invoice::where('status', Invoice::STATUS_PENDING)
                ->where('due_date', $targetDate)
                ->with(['subscription.mess', 'subscription.plan'])
                ->get();

            foreach ($pendingInvoices as $invoice) {
                try {
                    $this->sendInvoiceReminderNotification($invoice, $days);
                    $results['reminders_sent']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Send individual invoice reminder notification.
     *
     * @param Invoice $invoice
     * @param int $daysBeforeDue
     * @return void
     */
    protected function sendInvoiceReminderNotification(Invoice $invoice, int $daysBeforeDue): void
    {
        $reminderData = [
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->total_amount,
            'due_date' => $invoice->due_date,
            'days_until_due' => $daysBeforeDue,
            'mess_name' => $invoice->subscription->mess->name,
            'plan_name' => $invoice->subscription->plan->name,
            'billing_email' => $invoice->billing_email,
        ];

        // In real implementation, would send email/SMS/push notification
        \Log::info("Invoice reminder sent", $reminderData);
    }

    /**
     * Generate billing calendar for a subscription.
     *
     * @param Subscription $subscription
     * @param int $periodsAhead
     * @return array
     */
    public function generateBillingCalendar(Subscription $subscription, int $periodsAhead = 12): array
    {
        $calendar = [];
        $currentDate = $subscription->next_billing_date ?? $subscription->expires_at;
        $packageDuration = $subscription->package->duration;

        for ($i = 0; $i < $periodsAhead; $i++) {
            $amount = $subscription->package->price;
            $discount = 0;

            // Apply loyalty discount for future periods
            if ($i > 6) {
                $discount = $amount * 0.05;
            }

            $calendar[] = [
                'period' => $i + 1,
                'billing_date' => $currentDate->copy(),
                'period_start' => $currentDate->copy(),
                'period_end' => $currentDate->copy()->addDays($packageDuration),
                'amount' => $amount,
                'discount' => $discount,
                'final_amount' => $amount - $discount,
                'status' => $i === 0 ? 'upcoming' : 'projected',
                'invoice_generation_date' => $currentDate->copy()->subDays(7),
            ];

            $currentDate = $currentDate->copy()->addDays($packageDuration);
        }

        return [
            'subscription_id' => $subscription->id,
            'plan_name' => $subscription->plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'current_status' => $subscription->status,
            'calendar' => $calendar,
            'projected_annual_revenue' => collect($calendar)->sum('final_amount'),
        ];
    }

    /**
     * Get automatic billing summary for dashboard.
     *
     * @param array $filters
     * @return array
     */
    public function getAutomaticBillingSummary(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $filters['date_to'] ?? Carbon::now();

        $automaticInvoices = Invoice::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('notes', 'like', '%automatic%');

        $automaticTransactions = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('notes', 'like', '%automatic%');

        $totalAttempts = $automaticTransactions->count();
        $successfulAttempts = $automaticTransactions->where('status', Transaction::STATUS_COMPLETED)->count();

        return [
            'invoices_generated' => $automaticInvoices->count(),
            'payments_attempted' => $totalAttempts,
            'payments_successful' => $successfulAttempts,
            'payment_success_rate' => $totalAttempts > 0 ? round(($successfulAttempts / $totalAttempts) * 100, 2) : 100,
            'revenue_collected' => $automaticTransactions->where('status', Transaction::STATUS_COMPLETED)->sum('amount'),
            'upcoming_invoices' => Invoice::where('status', Invoice::STATUS_PENDING)
                ->where('due_date', '>', now())
                ->where('due_date', '<=', now()->addDays(7))
                ->count(),
            'overdue_invoices' => Invoice::where('status', Invoice::STATUS_OVERDUE)->count(),
        ];
    }
}

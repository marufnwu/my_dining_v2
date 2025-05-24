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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
     * Apply payment to a subscription.
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
     * Check if a mess has access to a specific feature.
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
}

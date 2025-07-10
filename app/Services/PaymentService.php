<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Helpers\Pipeline;
use App\Models\ManualPayment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): Pipeline
    {
        $methods = PaymentMethod::where('enabled', true)
            ->orderBy('display_order')
            ->get();

        return Pipeline::success(data: $methods);
    }

    /**
     * Submit a manual payment request
     */
    public function submitManualPayment(array $data): Pipeline
    {
        try {
            DB::beginTransaction();

            $payment = ManualPayment::create([
                'user_id' => $data['user_id'],
                'subscription_id' => $data['subscription_id'],
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['amount'],
                'transaction_id' => $data['transaction_id'],
                'status' => PaymentStatus::PENDING->value,
                'submitted_at' => now(),
                'notes' => $data['notes'] ?? null,
                'proof_url' => $data['proof_url'] ?? null,
            ]);

            DB::commit();
            return Pipeline::success(data: $payment);
        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: 'Failed to submit payment: ' . $e->getMessage());
        }
    }

    /**
     * Review a manual payment
     */
    public function reviewManualPayment(ManualPayment $payment, string $status, User $reviewer, ?string $notes = null): Pipeline
    {
        if (!in_array($status, [PaymentStatus::APPROVED->value, PaymentStatus::REJECTED->value])) {
            return Pipeline::error(message: 'Invalid status');
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'notes' => $notes ?? $payment->notes
            ]);

            // If payment is approved, update subscription status
            if ($status === PaymentStatus::APPROVED->value) {
                $subscription = $payment->subscription;
                if ($subscription) {
                    $this->activateSubscription($subscription);
                }
            }

            DB::commit();
            return Pipeline::success(data: $payment->fresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: 'Failed to review payment: ' . $e->getMessage());
        }
    }

    /**
     * Activate a subscription after successful payment
     */
    protected function activateSubscription(Subscription $subscription): void
    {
        $now = now();

        // If subscription hasn't started yet, set start date to now
        if (!$subscription->start_at) {
            $subscription->start_at = $now;
        }

        // Set end date based on duration
        if ($subscription->duration) {
            $subscription->end_at = $now->addDays($subscription->duration);
        }

        $subscription->is_active = true;
        $subscription->save();
    }

    /**
     * List manual payments with optional filters
     */
    public function listManualPayments(array $filters = []): Pipeline
    {
        $query = ManualPayment::with(['user', 'subscription', 'paymentMethod', 'reviewer'])
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['user_id']), function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->orderBy('submitted_at', 'desc');

        $payments = $query->get();
        return Pipeline::success(data: $payments);
    }
}

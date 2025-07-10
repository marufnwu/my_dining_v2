<?php

namespace App\Policies;

use App\Models\ManualPayment;
use App\Models\PaymentMethod;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine if the user can review manual payments
     */
    public function reviewPayments(User $user): bool
    {
        // Check if user has admin role in their mess
        return $user->messUser?->role?->is_admin ?? false;
    }

    /**
     * Determine if the user can manage payment methods
     */
    public function managePaymentMethods(User $user): bool
    {
        // Check if user has admin role in their mess
        return $user->messUser?->role?->is_admin ?? false;
    }

    /**
     * Determine if the user can view a specific manual payment
     */
    public function viewManualPayment(User $user, ManualPayment $payment): bool
    {
        // Users can view their own payments or if they are admins
        return $user->id === $payment->user_id || $this->reviewPayments($user);
    }

    /**
     * Determine if the user can update payment method settings
     */
    public function updatePaymentMethod(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->managePaymentMethods($user);
    }
}

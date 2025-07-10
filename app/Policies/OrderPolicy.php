<?php

namespace App\Policies;

use App\Models\SubscriptionOrder;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders
     */
    public function viewAny(User $user): bool
    {
        // Users can view orders for their mess
        return (bool) $user->messUser;
    }

    /**
     * Determine if the user can view the order
     */
    public function view(User $user, SubscriptionOrder $order): bool
    {
        // Users can view orders for their mess
        return $user->messUser && $user->messUser->mess_id === $order->mess_id;
    }

    /**
     * Determine if the user can update the order
     */
    public function update(User $user, SubscriptionOrder $order): bool
    {
        // Only mess admins can update orders
        return $user->messUser &&
               $user->messUser->mess_id === $order->mess_id &&
               $user->messUser->role?->is_admin;
    }

    /**
     * Determine if the user can delete the order
     */
    public function delete(User $user, SubscriptionOrder $order): bool
    {
        // Only mess admins can delete orders
        return $user->messUser &&
               $user->messUser->mess_id === $order->mess_id &&
               $user->messUser->role?->is_admin;
    }
}

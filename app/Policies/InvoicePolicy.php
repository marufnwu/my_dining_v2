<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices
     */
    public function viewAny(User $user): bool
    {
        // Users can view invoices for their mess
        return (bool) $user->messUser;
    }

    /**
     * Determine if the user can view the invoice
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Users can view invoices for their mess
        return $user->messUser && $user->messUser->mess_id === $invoice->mess_id;
    }

    /**
     * Determine if the user can download the invoice
     */
    public function download(User $user, Invoice $invoice): bool
    {
        // Users can download invoices for their mess
        return $user->messUser && $user->messUser->mess_id === $invoice->mess_id;
    }

    /**
     * Determine if the user can update the invoice
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Only mess admins can update invoices
        return $user->messUser &&
               $user->messUser->mess_id === $invoice->mess_id &&
               $user->messUser->role?->is_admin;
    }
}

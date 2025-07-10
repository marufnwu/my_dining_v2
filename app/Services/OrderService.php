<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\SubscriptionOrder;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new order for a subscription
     */
    public function createOrder(Subscription $subscription, array $data = []): Pipeline
    {
        try {
            $order = SubscriptionOrder::create(array_merge([
                'mess_id' => $subscription->mess_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'plan_package_id' => $subscription->plan_package_id,
                'amount' => $subscription->package->price,
                'currency' => 'USD',
                'status' => SubscriptionOrder::STATUS_PENDING,
                'payment_method' => $subscription->payment_method,
                'payment_provider' => $subscription->payment_provider,
            ], $data));

            // Update subscription with new order
            $subscription->update([
                'last_order_id' => $order->id
            ]);

            return Pipeline::success(data: $order);
        } catch (\Exception $e) {
            return Pipeline::error(message: 'Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Record a transaction for an order
     */
    public function recordTransaction(SubscriptionOrder $order, array $data = []): Pipeline
    {
        try {
            $transaction = Transaction::create(array_merge([
                'mess_id' => $order->mess_id,
                'subscription_id' => $order->subscription_id,
                'order_id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'payment_method' => $order->payment_method,
                'payment_provider' => $order->payment_provider,
                'status' => Transaction::STATUS_PENDING,
            ], $data));

            // Update subscription with transaction info
            $subscription = $order->subscription;
            $subscription->update([
                'last_transaction_id' => $transaction->id,
                'total_spent' => $subscription->total_spent + $transaction->amount
            ]);

            return Pipeline::success(data: $transaction);
        } catch (\Exception $e) {
            return Pipeline::error(message: 'Failed to record transaction: ' . $e->getMessage());
        }
    }

    /**
     * Generate an invoice for an order
     */
    public function generateInvoice(SubscriptionOrder $order, array $data = []): Pipeline
    {
        try {
            $invoice = Invoice::create(array_merge([
                'mess_id' => $order->mess_id,
                'order_id' => $order->id,
                'transaction_id' => null, // Will be updated when transaction is completed
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => $order->amount,
                'currency' => $order->currency,
                'tax_amount' => 0, // Configure tax calculation if needed
                'total_amount' => $order->amount,
                'due_date' => Carbon::now()->addDays(7),
                'status' => Invoice::STATUS_PENDING,
            ], $data));

            return Pipeline::success(data: $invoice);
        } catch (\Exception $e) {
            return Pipeline::error(message: 'Failed to generate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Update order status and related records
     */
    public function updateOrderStatus(SubscriptionOrder $order, string $status, ?string $errorMessage = null): Pipeline
    {
        try {
            $order->update(['status' => $status]);

            // Update related transaction if exists
            if ($transaction = $order->transactions()->latest()->first()) {
                $transaction->update([
                    'status' => $status,
                    'error_message' => $errorMessage
                ]);
            }

            // Update related invoice if exists
            if ($invoice = $order->invoice) {
                $invoice->update([
                    'status' => $status === SubscriptionOrder::STATUS_COMPLETED ? Invoice::STATUS_PAID : Invoice::STATUS_PENDING,
                    'paid_at' => $status === SubscriptionOrder::STATUS_COMPLETED ? now() : null
                ]);
            }

            return Pipeline::success(data: $order->fresh());
        } catch (\Exception $e) {
            return Pipeline::error(message: 'Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        return "{$prefix}-{$date}-{$random}";
    }
}

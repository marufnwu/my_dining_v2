<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    protected $fillable = [
        'mess_id',
        'plan_id',
        'plan_package_id',
        'starts_at',
        'expires_at',
        'trial_ends_at',
        'grace_period_ends_at',
        'admin_grace_period_days',
        'status',
        'payment_method',
        'payment_id',
        'is_canceled',
        'canceled_at',
        // Order/Transaction related fields
        'last_order_id',
        'last_transaction_id',
        'payment_status',
        'billing_cycle',
        'next_billing_date',
        'total_spent',
        'invoice_reference',
    ];

    protected $dates = [
        'starts_at',
        'expires_at',
        'trial_ends_at',
        'grace_period_ends_at',
        'canceled_at',
        'next_billing_date',
    ];

    protected $casts = [
        'is_canceled' => 'boolean',
        'total_spent' => 'decimal:2',
        'admin_grace_period_days' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_TRIAL = 'trial';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_GRACE_PERIOD = 'grace_period';

    /**
     * Get the mess that owns the subscription.
     */
    public function mess()
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the plan that owns the subscription.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the package that owns the subscription.
     */
    public function package()
    {
        return $this->belongsTo(PlanPackage::class, 'plan_package_id');
    }

    /**
     * Get the orders associated with this subscription.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(SubscriptionOrder::class);
    }

    /**
     * Get the latest order for this subscription.
     */
    public function latestOrder(): HasOne
    {
        return $this->hasOne(SubscriptionOrder::class)->latest();
    }

    /**
     * Get the transactions associated with this subscription.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the latest transaction for this subscription.
     */
    public function latestTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->latest();
    }

    /**
     * Get the invoices associated with this subscription.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get feature usage records for this subscription.
     */
    public function featureUsages(): HasMany
    {
        return $this->hasMany(FeatureUsage::class);
    }

    /**
     * Check if subscription is in grace period.
     */
    public function inGracePeriod(): bool
    {
        return $this->status === self::STATUS_GRACE_PERIOD &&
               $this->grace_period_ends_at > Carbon::now();
    }

    /**
     * Check if subscription has grace period expired.
     */
    public function gracePeriodExpired(): bool
    {
        return $this->grace_period_ends_at &&
               $this->grace_period_ends_at <= Carbon::now();
    }

    /**
     * Calculate total grace period (default + admin).
     */
    public function getTotalGracePeriodDays(): int
    {
        return $this->package->default_grace_period_days + $this->admin_grace_period_days;
    }

    /**
     * Calculate grace period end date.
     */
    public function calculateGracePeriodEndDate(): Carbon
    {
        $totalGraceDays = $this->getTotalGracePeriodDays();
        return $this->expires_at->copy()->addDays($totalGraceDays);
    }

    /**
     * Check if subscription is active (including grace period).
     */
    public function isActiveOrInGrace(): bool
    {
        return $this->isActive() || $this->inGracePeriod();
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->expires_at > Carbon::now();
    }

    /**
     * Check if subscription is in trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL &&
               $this->trial_ends_at > Carbon::now();
    }

    /**
     * Check if subscription has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at <= Carbon::now();
    }

    /**
     * Create a new order for this subscription.
     */
    public function createOrder(array $attributes = []): SubscriptionOrder
    {
        $order = $this->orders()->create(array_merge([
            'mess_id' => $this->mess_id,
            'plan_id' => $this->plan_id,
            'plan_package_id' => $this->plan_package_id,
            'amount' => $this->package->price,
            'currency' => 'USD',
            'status' => SubscriptionOrder::STATUS_PENDING,
        ], $attributes));

        $this->update([
            'last_order_id' => $order->id
        ]);

        return $order;
    }

    /**
     * Record a transaction for this subscription.
     */
    public function recordTransaction(array $attributes = []): Transaction
    {
        $transaction = $this->transactions()->create(array_merge([
            'mess_id' => $this->mess_id,
            'order_id' => $this->last_order_id,
            'amount' => $this->package->price,
            'currency' => 'USD',
            'payment_method' => $this->payment_method,
            'status' => Transaction::STATUS_PENDING,
        ], $attributes));

        $this->update([
            'last_transaction_id' => $transaction->id,
            'total_spent' => $this->total_spent + $transaction->amount
        ]);

        return $transaction;
    }

    /**
     * Generate an invoice for this subscription.
     */
    public function generateInvoice(): Invoice
    {
        $latestOrder = $this->latestOrder;

        return $this->invoices()->create([
            'mess_id' => $this->mess_id,
            'order_id' => $latestOrder->id ?? null,
            'transaction_id' => $this->last_transaction_id,
            'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad($this->id, 5, '0', STR_PAD_LEFT),
            'amount' => $this->package->price,
            'currency' => 'USD',
            'tax_amount' => 0, // You can calculate tax if needed
            'total_amount' => $this->package->price,
            'due_date' => Carbon::now()->addDays(7),
            'status' => Invoice::STATUS_PENDING,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SubscriptionOrder extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'subscription_id',
        'mess_id',
        'plan_id',
        'plan_package_id',
        'order_number',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'billing_address',
        'billing_email',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription that owns the order.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the mess that owns the order.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the plan associated with the order.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the package associated with the order.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PlanPackage::class, 'plan_package_id');
    }

    /**
     * Get the transaction associated with the order.
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'order_id');
    }

    /**
     * Get the invoice associated with the order.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . substr(uniqid(), -5);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }

            if (empty($order->total_amount)) {
                $order->total_amount = $order->amount - $order->discount_amount + $order->tax_amount;
            }
        });
    }

    /**
     * Mark the order as complete.
     */
    public function markAsComplete(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->payment_status = 'paid';
        return $this->save();
    }

    /**
     * Mark the order as failed.
     */
    public function markAsFailed(string $notes = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->payment_status = 'failed';

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'subscription_id',
        'mess_id',
        'order_id',
        'transaction_reference',
        'payment_method',
        'payment_provider',
        'payment_provider_reference',
        'amount',
        'currency',
        'status',
        'notes',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the subscription that owns the transaction.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the mess that owns the transaction.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the order that owns the transaction.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class, 'order_id');
    }

    /**
     * Generate a unique transaction reference.
     */
    public static function generateTransactionReference(): string
    {
        return 'TXN-' . date('Ymd') . '-' . substr(uniqid(), -7);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_reference)) {
                $transaction->transaction_reference = self::generateTransactionReference();
            }
        });
    }

    /**
     * Mark the transaction as complete.
     */
    public function markAsComplete($providerReference = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();

        if ($providerReference) {
            $this->payment_provider_reference = $providerReference;
        }

        return $this->save();
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(string $notes = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->processed_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }
}

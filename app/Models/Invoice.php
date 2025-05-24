<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'subscription_id',
        'mess_id',
        'order_id',
        'transaction_id',
        'invoice_number',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'billing_address',
        'billing_email',
        'due_date',
        'issued_date',
        'paid_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'issued_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Get the subscription that owns the invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the mess that owns the invoice.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the order associated with the invoice.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class, 'order_id');
    }

    /**
     * Get the transaction associated with the invoice.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->issued_date)) {
                $invoice->issued_date = now();
            }

            if (empty($invoice->total_amount)) {
                $invoice->total_amount = $invoice->amount - $invoice->discount_amount + $invoice->tax_amount;
            }
        });
    }

    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_date = now();
        return $this->save();
    }

    /**
     * Generate a PDF invoice.
     */
    public function generatePdf()
    {
        // Implement PDF generation here using a library like dompdf
        // This is just a stub
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('invoices.pdf', ['invoice' => $this]);
        return $pdf;
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status === self::STATUS_PENDING;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'mess_user_id',
        'mess_id',
        'type',
        'purchase_type',
        'price',
        'product',
        'product_json',
        'deposit_request',
        'status',
        'comment',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'price' => 'float',
        'deposit_request' => 'boolean',
        'purchase_type' => 'integer',
        'status' => 'integer',
        'product_json' => 'json',
    ];

    /**
     * Get the month that owns the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    /**
     * Get the messUser that owns the PurchaseRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function messUser(): BelongsTo
    {
        return $this->belongsTo(MessUser::class);
    }
}

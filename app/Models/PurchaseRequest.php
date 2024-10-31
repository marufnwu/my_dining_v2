<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'user_id',
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
        'user_id' => 'integer',
        'mess_id' => 'integer',
        'purchase_type' => 'integer',
        'status' => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'enabled',
        'instructions',
        'config',
        'display_order'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'json',
        'display_order' => 'integer'
    ];

    public function manualPayments()
    {
        return $this->hasMany(ManualPayment::class);
    }
}

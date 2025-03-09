<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory, \App\Traits\HasModelName;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'month_id',
        'mess_user_id',
        'amount',
        'date',
        'type',
        'mess_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'date' => 'datetime',
        'type' => 'integer',

    ];

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }
}

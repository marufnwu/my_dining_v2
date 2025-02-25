<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'month_id',
        'user_id',
        'amount',
        'date',
        'type',
        'action_user_id',
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
        'action_user_id' => 'integer',
        'mess_id' => 'integer',
    ];
}

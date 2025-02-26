<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitiateUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'month_id',
        'mess_user_id',
        'mess_id',
        'year',
        'month',
        'active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'active' => 'boolean',
        'month_id' => 'integer',


    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
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
        'mess_id',
        'date',
        'breakfast',
        'lunch',
        'dinner',
        'manager_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'breakfast' => 'float',
        'lunch' => 'float',
        'dinner' => 'float',
        'month_id' => 'integer',
        'user_id' => 'integer',
        'mess_id' => 'integer',
        'manager_id' => 'integer',
    ];
}

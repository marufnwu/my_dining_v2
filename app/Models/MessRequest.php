<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'old_user_id',
        'new_user_id',
        'request_date',
        'accept_date',
        'old_mess_id',
        'new_mess_id',
        'accept_by',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'datetime',
        'accept_date' => 'datetime',
        'old_user_id' => 'integer',
        'new_user_id' => 'integer',
        'old_mess_id' => 'integer',
        'new_mess_id' => 'integer',
        'accept_by' => 'integer',
        'status' => 'integer',
    ];
}

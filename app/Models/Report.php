<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'mess_id',
        'month',
        'year',
        'creation_date',
        'pdf',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'creation_date' => 'datetime',
        'user_id' => 'integer',
        'mess_id' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
    ];
}

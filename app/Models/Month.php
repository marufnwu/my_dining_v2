<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    protected $fillable = [
        'mess_id',
        'name',
        'type',
        'start_at',
        'end_at',
    ];
}

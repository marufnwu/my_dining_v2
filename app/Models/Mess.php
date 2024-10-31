<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mess extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'super_user_id',
        'status',
        'ad_free',
        'all_user_add_meal',
        'fund',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'super_user_id' => 'integer',
        'status' => 'integer',
        'ad_free' => 'integer',
        'all_user_add_meal' => 'integer',
        'fund' => 'integer',
    ];
}

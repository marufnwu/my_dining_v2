<?php

namespace App\Models;

use App\Traits\HasModelName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, HasModelName;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'image_url',
        'action_url',
        'action_type',
        'visible',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'visible' => 'boolean',
        'action_type' => 'integer',
    ];
}

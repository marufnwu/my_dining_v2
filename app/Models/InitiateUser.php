<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'month_id' => 'integer',


    ];

    /**
     * Get the user that owns the InitiateUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function messUser(): BelongsTo
    {
        return $this->belongsTo(MessUser::class)->with("user");
    }
}

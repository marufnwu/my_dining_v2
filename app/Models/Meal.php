<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meal extends Model
{
    use \App\Traits\HasModelName;

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
        'date',
        'breakfast',
        'lunch',
        'dinner',
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
    ];

    /**
     * Get the month that owns the Meal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    /**
     * Get the messUser that owns the Meal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function messUser(): BelongsTo
    {
        return $this->belongsTo(MessUser::class);
    }
}

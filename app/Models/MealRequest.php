<?php

namespace App\Models;

use App\Enums\MealRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealRequest extends Model
{
    use HasFactory, \App\Traits\HasModelName;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mess_user_id',
        'mess_id',
        'month_id',
        'date',
        'breakfast',
        'lunch',
        'dinner',
        'status',
        'comment',
        'approved_by',
        'approved_at',
        'rejected_reason',
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
        'status' => MealRequestStatus::class,
        'approved_at' => 'datetime',
        'mess_user_id' => 'integer',
        'mess_id' => 'integer',
        'month_id' => 'integer',
        'approved_by' => 'integer',
    ];

    /**
     * Get the mess user that owns the meal request
     */
    public function messUser(): BelongsTo
    {
        return $this->belongsTo(MessUser::class);
    }

    /**
     * Get the mess that owns the meal request
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the month that owns the meal request
     */
    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    /**
     * Get the mess user who approved the meal request
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(MessUser::class, 'approved_by');
    }
}

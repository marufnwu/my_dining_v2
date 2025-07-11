<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'mess_user_id',
        'mess_id',
        "month_id",
        'price',
        'product',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'price' => 'integer',



    ];

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

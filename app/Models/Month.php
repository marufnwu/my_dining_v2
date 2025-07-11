<?php

namespace App\Models;

use App\Enums\MonthType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Month extends Model
{
    protected $fillable = [
        'mess_id',
        'name',
        'type',
        'start_at',
        'end_at',
        "is_active"
    ];

    protected $appends = [
        "is_active"
    ];

    public function getIsActiveAttribute(): bool
    {
        if ($this->type == MonthType::MANUAL->value) {
            if ($this->end_at) {
                return false;
            }
        }


        if ($this->type == MonthType::AUTOMATIC->value) {
            if ($this->end_at < Carbon::now()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all of the initiatedUser for the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function initiatedUser(): HasMany
    {
        return $this->hasMany(InitiateUser::class);
    }

    public function notInitiatedUser()
    {
        return $this->mess->messUsers()->whereDoesntHave('initiatedUser', function ($query) {
            $query->where('month_id', $this->id); // Filter by the current month
        });
    }


    /**
     * Get the mess that owns the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get all of the meals for the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    /**
     * Get all of the deposits for the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * Get all of the purchases for the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function otherCosts(): HasMany
    {
        return $this->hasMany(OtherCost::class);
    }

    /**
     * Get all of the messUsers for the Month
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messUsers(): HasMany
    {
        return $this->hasMany(MessUser::class, 'foreign_key', 'local_key');
    }
}

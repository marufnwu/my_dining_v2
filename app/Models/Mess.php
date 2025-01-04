<?php

namespace App\Models;

use App\Enums\MessStatus;
use App\Enums\MessUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'status',
        'ad_free',
        'all_user_add_meal',
        'fund_add_enabled',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => MessStatus::class,
        'ad_free' => "boolean",
        'all_user_add_meal' => 'boolean',
        'fund_add_enabled' => 'boolean',
    ];

     /**
     * Get the users associated with the mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messUsers(): HasMany
    {
        return $this->hasMany(MessUser::class);
    }

    /**
     * Get all of the roles for the Mess
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(MessRole::class);
    }

    public function adminRole() : HasOne
    {
        return $this->hasOne(MessRole::class)->where('role', MessUserRole::Admin->value);
    }

    public function managerRole() : HasOne
    {
        return $this->hasOne(MessRole::class)->where('role', MessUserRole::Manager->value);
    }

}

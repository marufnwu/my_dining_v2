<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPackage extends Model
{
    protected $fillable = ['plan_id', 'is_trial', 'is_free', 'duration', 'price', 'is_active'];

    /**
     * Get the plan that owns the package.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscriptions that use this package.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}

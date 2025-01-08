<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['keyword', 'name', 'is_free', 'is_active'];

    /**
     * Get the packages associated with the plan.
     */
    public function packages()
    {
        return $this->hasMany(PlanPackage::class);
    }

    /**
     * Get the features associated with the plan.
     */
    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get the subscriptions associated with the plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}

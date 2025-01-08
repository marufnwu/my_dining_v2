<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureUsage extends Model
{
    protected $fillable = ['subscription_id', 'plan_feature_id', 'used'];

    /**
     * Get the subscription associated with this feature usage.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the plan feature associated with this feature usage.
     */
    public function feature()
    {
        return $this->belongsTo(PlanFeature::class, 'plan_feature_id');
    }
}

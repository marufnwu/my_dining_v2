<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureUsage extends Model
{
    use \App\Traits\HasModelName;
    protected $fillable = [
        'subscription_id',
        'plan_feature_id',
        'used_count',
        'reset_at'
    ];

    protected $dates = [
        'reset_at'
    ];

     /**
     * Get the subscription that owns the usage.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the feature this usage is for.
     */
    public function feature()
    {
        return $this->belongsTo(PlanFeature::class, 'plan_feature_id');
    }

    /**
     * Increment the usage count.
     */
    public function increment($amount = 1)
    {
        $this->used_count += $amount;
        return $this->save();
    }

    /**
     * Check if the usage is within limits.
     */
    public function withinLimits()
    {
        $feature = $this->feature;

        // If feature is not countable, it's always available
        if (!$feature->is_countable) {
            return true;
        }

        return $this->used_count < $feature->usage_limit;
    }
}

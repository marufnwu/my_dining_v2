<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FeatureUsage extends Model
{
    use \App\Traits\HasModelName;

    protected $fillable = [
        'subscription_id',
        'plan_feature_id',
        'used',
        'reset_at',
        'reset_period' // 'monthly', 'lifetime', 'yearly', etc.
    ];

    protected $dates = [
        'reset_at'
    ];

    protected $casts = [
        'used' => 'integer',
        'reset_at' => 'datetime'
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
    public function incrementUsage($amount = 1)
    {
        $this->used += $amount;
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

        // Check if we need to reset based on reset period
        $this->checkAndResetIfNeeded();

        return $this->used < $feature->usage_limit;
    }

    /**
     * Check if reset is needed and perform reset
     */
    public function checkAndResetIfNeeded()
    {
        if (!$this->reset_at || $this->reset_at->isPast()) {
            $this->resetUsage();
        }
    }

    /**
     * Reset usage based on reset period
     */
    public function resetUsage()
    {
        $this->used = 0;

        // Set next reset date based on reset period
        switch ($this->reset_period) {
            case 'monthly':
                $this->reset_at = now()->addMonth();
                break;
            case 'yearly':
                $this->reset_at = now()->addYear();
                break;
            case 'weekly':
                $this->reset_at = now()->addWeek();
                break;
            case 'daily':
                $this->reset_at = now()->addDay();
                break;
            case 'lifetime':
            default:
                $this->reset_at = null; // Never reset
                break;
        }

        $this->save();
    }

    /**
     * Get remaining usage
     */
    public function getRemainingAttribute()
    {
        $feature = $this->feature;
        if (!$feature || !$feature->is_countable) {
            return null;
        }

        $this->checkAndResetIfNeeded();
        return max(0, $feature->usage_limit - $this->used);
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute()
    {
        $feature = $this->feature;
        if (!$feature || !$feature->is_countable) {
            return 0;
        }

        $this->checkAndResetIfNeeded();
        return min(100, ($this->used / $feature->usage_limit) * 100);
    }
}

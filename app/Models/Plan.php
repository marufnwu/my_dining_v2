<?php

namespace App\Models;

use App\Constants\SubPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Get the default plan for free tier users.
     */
    public static function getDefaultPlan(): ?Plan
    {
        return Cache::remember('default_plan', 3600, function () {
            return self::where('keyword', SubPlan::DEFAULT)
                ->where('is_active', true)
                ->with(['features', 'packages'])
                ->first();
        });
    }

    /**
     * Clear default plan cache.
     */
    public static function clearDefaultPlanCache(): void
    {
        Cache::forget('default_plan');
    }
}

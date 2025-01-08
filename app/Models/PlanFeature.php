<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $fillable = ['plan_id', 'name', 'description', 'is_countable', 'usage_limit', 'is_active'];

    /**
     * Get the plan that owns the feature.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the feature usages for this feature.
     */
    public function featureUsages()
    {
        return $this->hasMany(FeatureUsage::class);
    }
}

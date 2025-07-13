<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'name', // Keep using name for simplicity
        'description',
        'is_countable',
        'usage_limit',
        'is_active',
        'reset_period',
        'category' // Add category for organization
    ];

    protected $casts = [
        'is_countable' => 'boolean',
        'is_active' => 'boolean',
        'usage_limit' => 'integer'
    ];

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

    /**
     * Get the dynamic feature definition from config
     */
    public function getFeatureDefinition()
    {
        return \App\Config\FeatureConfig::getFeatureDefinition($this->name);
    }

    /**
     * Get default reset period for feature type
     */
    public function getDefaultResetPeriodAttribute()
    {
        $featureDefinition = $this->getFeatureDefinition();
        return $featureDefinition['reset_period'] ?? 'monthly';
    }

    /**
     * Get free tier limit for this feature
     */
    public function getFreeLimitAttribute()
    {
        $featureDefinition = $this->getFeatureDefinition();
        return $featureDefinition['free_limit'] ?? 0;
    }

    /**
     * Check if this plan feature is better than free tier
     */
    public function isBetterThanFreeTier()
    {
        $freeLimit = $this->free_limit;

        // For countable features, check if limit is higher
        if ($this->is_countable) {
            return $this->usage_limit > $freeLimit;
        }

        // For non-countable features, check if available in plan but not in free tier
        return $freeLimit === 0 && $this->is_active;
    }

    /**
     * Get upgrade benefit description
     */
    public function getUpgradeBenefitDescription()
    {
        $freeLimit = $this->free_limit;

        if ($this->is_countable) {
            if ($this->usage_limit > $freeLimit) {
                return "Upgrade from {$freeLimit} to {$this->usage_limit} {$this->name}";
            }
        }

        if ($freeLimit === 0 && $this->is_active) {
            return "Get access to {$this->name}";
        }

        return null;
    }

    /**
     * Scope to get features by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get active features
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

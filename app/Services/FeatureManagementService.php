<?php

namespace App\Services;

use App\Models\Feature;
use App\Config\FeatureConfig;
use App\Helpers\Pipeline;

class FeatureManagementService
{
    /**
     * Create a new feature
     */
    public function createFeature(array $featureData): Pipeline
    {
        try {
            $feature = Feature::create($featureData);

            // Clear cache
            FeatureConfig::clearCache();

            return Pipeline::success($feature, 'Feature created successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to create feature: ' . $e->getMessage());
        }
    }

    /**
     * Update a feature
     */
    public function updateFeature(Feature $feature, array $featureData): Pipeline
    {
        try {
            $feature->update($featureData);

            // Clear cache
            FeatureConfig::clearCache();

            return Pipeline::success($feature, 'Feature updated successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to update feature: ' . $e->getMessage());
        }
    }

    /**
     * Delete a feature
     */
    public function deleteFeature(Feature $feature): Pipeline
    {
        try {
            // Check if feature is being used in any plans
            if ($feature->planFeatures()->count() > 0) {
                return Pipeline::error('Cannot delete feature: It is being used in existing plans');
            }

            $feature->delete();

            // Clear cache
            FeatureConfig::clearCache();

            return Pipeline::success(null, 'Feature deleted successfully');
        } catch (\Exception $e) {
            return Pipeline::error('Failed to delete feature: ' . $e->getMessage());
        }
    }

    /**
     * Get all features with pagination
     */
    public function getFeatures(int $perPage = 15): Pipeline
    {
        try {
            $features = Feature::paginate($perPage);
            return Pipeline::success($features);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to fetch features: ' . $e->getMessage());
        }
    }

    /**
     * Get feature by ID
     */
    public function getFeature(int $id): Pipeline
    {
        try {
            $feature = Feature::findOrFail($id);
            return Pipeline::success($feature);
        } catch (\Exception $e) {
            return Pipeline::error('Feature not found');
        }
    }

    /**
     * Get features by category
     */
    public function getFeaturesByCategory(string $category): Pipeline
    {
        try {
            $features = Feature::getByCategory($category);
            return Pipeline::success($features);
        } catch (\Exception $e) {
            return Pipeline::error('Failed to fetch features by category: ' . $e->getMessage());
        }
    }

    /**
     * Get available reset periods
     */
    public function getResetPeriods(): array
    {
        return [
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'weekly' => 'Weekly',
            'daily' => 'Daily',
            'lifetime' => 'Lifetime'
        ];
    }

    /**
     * Get available categories
     */
    public function getCategories(): array
    {
        return [
            'members' => 'Members',
            'reports' => 'Reports',
            'notifications' => 'Notifications',
            'financial' => 'Financial',
            'management' => 'Management',
            'general' => 'General'
        ];
    }
}

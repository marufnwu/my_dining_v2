<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureManagementService;
use App\Models\Feature;
use Illuminate\Http\Request;
use App\Http\Requests\FeatureRequest;

class FeatureController extends Controller
{
    protected $featureService;

    public function __construct(FeatureManagementService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Get all features
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $result = $this->featureService->getFeatures($perPage);

        return $result->toApiResponse();
    }

    /**
     * Get feature by ID
     */
    public function show(int $id)
    {
        $result = $this->featureService->getFeature($id);
        return $result->toApiResponse();
    }

    /**
     * Create new feature
     */
    public function store(FeatureRequest $request)
    {
        $result = $this->featureService->createFeature($request->validated());
        return $result->toApiResponse();
    }

    /**
     * Update feature
     */
    public function update(FeatureRequest $request, Feature $feature)
    {
        $result = $this->featureService->updateFeature($feature, $request->validated());
        return $result->toApiResponse();
    }

    /**
     * Delete feature
     */
    public function destroy(Feature $feature)
    {
        $result = $this->featureService->deleteFeature($feature);
        return $result->toApiResponse();
    }

    /**
     * Get features by category
     */
    public function byCategory(string $category)
    {
        $result = $this->featureService->getFeaturesByCategory($category);
        return $result->toApiResponse();
    }

    /**
     * Get available reset periods
     */
    public function resetPeriods()
    {
        return response()->json([
            'data' => $this->featureService->getResetPeriods()
        ]);
    }

    /**
     * Get available categories
     */
    public function categories()
    {
        return response()->json([
            'data' => $this->featureService->getCategories()
        ]);
    }
}

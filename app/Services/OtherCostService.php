<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\OtherCost;
use App\Models\Month;

class OtherCostService
{
    /**
     * Add a new other cost.
     *
     * @param array $data
     * @return Pipeline
     */
    public function addOtherCost(Month $month, array $data): Pipeline
    {
        $otherCost = $month->otherCosts()->create($data);
        return Pipeline::success(data: $otherCost);
    }

    /**
     * Update an existing other cost.
     *
     * @param OtherCost $otherCost
     * @param array $data
     * @return Pipeline
     */
    public function updateOtherCost(Month $month, OtherCost $otherCost, array $data): Pipeline
    {
        $otherCost->update($data);
        return Pipeline::success(data: $otherCost->fresh());
    }

    /**
     * Delete an other cost.
     *
     * @param OtherCost $otherCost
     * @return Pipeline
     */
    public function deleteOtherCost(Month $month, OtherCost $otherCost): Pipeline
    {
        $otherCost->delete();
        return Pipeline::success(message: 'Other cost deleted successfully');
    }

    /**
     * Get a list of other costs.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function listOtherCosts(Month $month): Pipeline
    {
        $otherCosts = $month->otherCosts()
            ->with("messUser.user")
            ->orderBy('date', 'desc')
            ->get();

        $totalPrice = $otherCosts->sum('price');

        $data = [
            'purchases' => $otherCosts,
            'total_price' => $totalPrice,
        ];

        return Pipeline::success(data: $data);
    }
}

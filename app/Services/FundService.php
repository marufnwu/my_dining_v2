<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Fund;
use App\Models\Month;

class FundService
{
    /**
     * Add a new fund.
     *
     * @param array $data
     * @return Pipeline
     */
    public function addFund(array $data): Pipeline
    {
        $fund = Fund::create($data);
        return Pipeline::success(data: $fund);
    }

    /**
     * Update an existing fund.
     *
     * @param Fund $fund
     * @param array $data
     * @return Pipeline
     */
    public function updateFund(Fund $fund, array $data): Pipeline
    {
        $fund->update($data);
        return Pipeline::success(data: $fund->fresh());
    }

    /**
     * Delete a fund.
     *
     * @param Fund $fund
     * @return Pipeline
     */
    public function deleteFund(Fund $fund): Pipeline
    {
        $fund->delete();
        return Pipeline::success(message: 'Fund deleted successfully');
    }

    /**
     * Get a list of funds.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function listFunds(Month $month): Pipeline
    {
        $funds = Fund::where('month_id', $month->id)
            ->orderBy('date', 'desc')
            ->get();

        $totalAmount = $funds->sum('amount');

        $data = [
            'funds' => $funds,
            'total_amount' => $totalAmount,
        ];

        return Pipeline::success(data: $data);
    }
}

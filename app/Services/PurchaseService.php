<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Purchase;
use App\Models\Month;

class PurchaseService
{
    /**
     * Add a new purchase.
     *
     * @param array $data
     * @return Pipeline
     */
    public function addPurchase(array $data): Pipeline
    {
        $purchase = Purchase::create($data);
        return Pipeline::success(data: $purchase);
    }

    /**
     * Update an existing purchase.
     *
     * @param Purchase $purchase
     * @param array $data
     * @return Pipeline
     */
    public function updatePurchase(Purchase $purchase, array $data): Pipeline
    {
        $purchase->update($data);
        return Pipeline::success(data: $purchase->fresh());
    }

    /**
     * Delete a purchase.
     *
     * @param Purchase $purchase
     * @return Pipeline
     */
    public function deletePurchase(Purchase $purchase): Pipeline
    {
        $purchase->delete();
        return Pipeline::success(message: 'Purchase deleted successfully');
    }

    /**
     * Get a list of purchases.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function listPurchases(Month $month): Pipeline
    {
        $purchases = Purchase::where('month_id', $month->id)
            ->orderBy('date', 'desc')
            ->get();

        $totalPrice = $purchases->sum('price');

        $data = [
            'purchases' => $purchases,
            'total_price' => $totalPrice,
        ];

        return Pipeline::success(data: $data);
    }
}

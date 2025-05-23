<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\PurchaseRequest;
use App\Models\Month;
use App\Models\Purchase;

class PurchaseRequestService
{
    /**
     * Create a new purchase request.
     *
     * @param array $data
     * @return Pipeline
     */
    public function createPurchaseRequest(array $data): Pipeline
    {
        $purchaseRequest = PurchaseRequest::create($data);
        return Pipeline::success(data: $purchaseRequest);
    }

    /**
     * Update an existing purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @param array $data
     * @return Pipeline
     */
    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): Pipeline
    {
        $purchaseRequest->update($data);
        return Pipeline::success(data: $purchaseRequest->fresh());
    }

    /**
     * Update the status of a purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @param array $data
     * @return Pipeline
     */
    public function updateStatus(PurchaseRequest $purchaseRequest, array $data): Pipeline
    {
        $purchaseRequest->update([
            'status' => $data['status'],
            'comment' => $data['comment'] ?? $purchaseRequest->comment,
        ]);

        // If approved (assuming status code 1 is for approval), create actual purchase
        if ($data['status'] === 1) {
            $this->createPurchaseFromRequest($purchaseRequest);
        }

        return Pipeline::success(data: $purchaseRequest->fresh());
    }

    /**
     * Create a purchase from an approved purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @return void
     */
    private function createPurchaseFromRequest(PurchaseRequest $purchaseRequest): void
    {
        Purchase::create([
            'date' => $purchaseRequest->date,
            'mess_user_id' => $purchaseRequest->mess_user_id,
            'mess_id' => $purchaseRequest->mess_id,
            'month_id' => $purchaseRequest->month_id,
            'price' => $purchaseRequest->price,
            'product' => $purchaseRequest->product,
        ]);
    }

    /**
     * Delete a purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @return Pipeline
     */
    public function deletePurchaseRequest(PurchaseRequest $purchaseRequest): Pipeline
    {
        $purchaseRequest->delete();
        return Pipeline::success(message: 'Purchase request deleted successfully');
    }

    /**
     * Get a list of purchase requests with optional filters.
     *
     * @param Month $month
     * @param array $filters
     * @return Pipeline
     */
    public function listPurchaseRequests(Month $month, array $filters = []): Pipeline
    {
        $query = PurchaseRequest::where('month_id', $month->id)
            ->with("messUser.user")
            ->orderBy('date', 'desc');
            
        // Apply filters if provided
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['purchase_type'])) {
            $query->where('purchase_type', $filters['purchase_type']);
        }
        
        if (isset($filters['deposit_request'])) {
            $query->where('deposit_request', $filters['deposit_request']);
        }
        
        $purchaseRequests = $query->get();

        $totalPrice = $purchaseRequests->sum('price');

        $data = [
            'purchase_requests' => $purchaseRequests,
            'total_price' => $totalPrice,
        ];

        return Pipeline::success(data: $data);
    }
    
    /**
     * Get a specific purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @return Pipeline
     */
    public function getPurchaseRequest(PurchaseRequest $purchaseRequest): Pipeline
    {
        $purchaseRequest->load('messUser.user');
        return Pipeline::success(data: $purchaseRequest);
    }

    /**
     * Get total pending purchase request amount for a month.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function getTotalPendingRequests(Month $month): Pipeline
    {
        $pendingRequests = PurchaseRequest::where('month_id', $month->id)
            ->where('status', 0) // Assuming 0 is pending status
            ->count();
            
        $pendingAmount = PurchaseRequest::where('month_id', $month->id)
            ->where('status', 0)
            ->sum('price');

        $data = [
            'pending_count' => $pendingRequests,
            'pending_amount' => $pendingAmount,
        ];

        return Pipeline::success(data: $data);
    }
}
<?php

namespace App\Services;

use App\Enums\PurchaseRequestStatus;
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
     * Update a purchase request and handle approval and deposit requests.
     *
     * @param PurchaseRequest $purchaseRequest
     * @param array $data
     * @return Pipeline
     */
    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): Pipeline
    {
        $originalStatus = $purchaseRequest->status;
        $wasApproved = false;


        // Check if the status was changed to approved
        if (
            $originalStatus != PurchaseRequestStatus::APPROVED->value &&
            $purchaseRequest->status == PurchaseRequestStatus::APPROVED->value
        ) {
            $wasApproved = true;
        }

        // If the purchase request was approved, create a purchase
        if ($wasApproved) {


            $purchaseService = new PurchaseService();
            $depositService = new DepositService();

            // Create a purchase record
            $purchaseData = [
                'date' => $purchaseRequest->date,
                'mess_user_id' => $purchaseRequest->mess_user_id,
                'mess_id' => $purchaseRequest->mess_id,
                'month_id' => $purchaseRequest->month_id,
                'price' => $purchaseRequest->price,
                'product' => $purchaseRequest->product,
            ];

            $purchaseService->addPurchase($purchaseData);

            // If deposit request is enabled, create a deposit
            if ($purchaseRequest->deposit_request) {
                $depositData = [
                    'month_id' => $purchaseRequest->month_id,
                    'mess_user_id' => $purchaseRequest->mess_user_id,
                    'amount' => $purchaseRequest->price,
                    'date' => now(),
                    'type' => 1, // Assuming 1 is for regular deposits, adjust as needed
                    'mess_id' => $purchaseRequest->mess_id,
                ];

                $depositService->addDeposit($depositData);
            }
        }

        // Update the purchase request with the provided data
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

        // If approved (status code 1 is for approval), create actual purchase
        if ($data['status'] == PurchaseRequestStatus::APPROVED->value && $purchaseRequest->status != PurchaseRequestStatus::APPROVED->value) {

            $pr = $this->createPurchaseFromRequest($purchaseRequest);

            if (!$pr->isSuccess()) {
                return $pr;
            }

            // Process deposit if requested
            if ($data['is_deposit'] ?? false) {
                $dr = $this->createDepositFromRequest($purchaseRequest);

                if (!$pr->isSuccess()) {
                    return $dr;
                }
            }


        }

        if($data['status'] == PurchaseRequestStatus::REJECTED->value && $purchaseRequest->status == PurchaseRequestStatus::APPROVED->value){
            return Pipeline::error("Request has already marked as approve");
        }

        $purchaseRequest->update([
            'status' => $data['status'],
            'comment' => $data['comment'] ?? $purchaseRequest->comment,
        ]);

        return Pipeline::success(data: $purchaseRequest->fresh());
    }

    /**
     * Create a purchase record from an approved purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @return void
     */
    protected function createPurchaseFromRequest(PurchaseRequest $purchaseRequest): Pipeline
    {
        $purchaseData = [
            'date' => $purchaseRequest->date,
            'mess_user_id' => $purchaseRequest->mess_user_id,
            'mess_id' => $purchaseRequest->mess_id,
            'month_id' => $purchaseRequest->month_id,
            'price' => $purchaseRequest->price,
            'product' => $purchaseRequest->product,
        ];

        $service = new PurchaseService();

        return $service->addPurchase($purchaseData);
    }

    /**
     * Create a deposit record from an approved purchase request.
     *
     * @param PurchaseRequest $purchaseRequest
     * @return void
     */
    protected function createDepositFromRequest(PurchaseRequest $purchaseRequest): Pipeline
    {
        $depositData = [
            'month_id' => $purchaseRequest->month_id,
            'mess_user_id' => $purchaseRequest->mess_user_id,
            'amount' => $purchaseRequest->price,
            'date' => now(),
            'type' => 1, // Assuming 1 is for regular deposits
            'mess_id' => $purchaseRequest->mess_id,
        ];

        return (new DepositService())->addDeposit($depositData);
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

        // Filter by mess_user_id if provided (for regular users viewing their own requests)
        if (isset($filters['mess_user_id'])) {
            $query->where('mess_user_id', $filters['mess_user_id']);
        }

        $purchaseRequests = $query->get();

        $totalPrice = $purchaseRequests->sum('price');

        $data = [
            'purchases' => $purchaseRequests,
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

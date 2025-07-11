<?php

namespace App\Services;

use App\Enums\PurchaseRequestStatus;
use App\Helpers\Pipeline;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseRequestService
{
    protected NotificationService $notificationService;
    protected PurchaseService $purchaseService;
    protected DepositService $depositService;

    public function __construct(
        NotificationService $notificationService,
        PurchaseService $purchaseService,
        DepositService $depositService
    ) {
        $this->notificationService = $notificationService;
        $this->purchaseService = $purchaseService;
        $this->depositService = $depositService;
    }

    public function createPurchaseRequest(array $data): Pipeline
    {
        try {
            $purchaseRequest = PurchaseRequest::create($data);

            // Notify admins about new purchase request
            $this->notificationService->sendNotification([
                'title' => 'New Purchase Request',
                'body' => "{$purchaseRequest->messUser->user->name} requested to purchase {$purchaseRequest->product} for ৳{$purchaseRequest->price}",
                'type' => 'new_purchase_request',
                'is_broadcast' => true,
                'extra_data' => [
                    'purchase_request_id' => $purchaseRequest->id,
                    'product' => $purchaseRequest->product,
                    'price' => $purchaseRequest->price,
                    'user_id' => $purchaseRequest->messUser->user_id,
                    'user_name' => $purchaseRequest->messUser->user->name
                ]
            ]);

            return Pipeline::success($purchaseRequest);
        } catch (\Exception $e) {
            return Pipeline::error(message: $e->getMessage());
        }
    }

    public function updatePurchaseRequest(PurchaseRequest $purchaseRequest, array $data): Pipeline
    {
        if ($purchaseRequest->status != PurchaseRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot update non-pending purchase request");
        }

        try {
            $oldPrice = $purchaseRequest->price;
            $oldProduct = $purchaseRequest->product;

            $purchaseRequest->update($data);

            // Notify about update
            $this->notificationService->sendNotification([
                'title' => 'Purchase Request Updated',
                'body' => "{$purchaseRequest->messUser->user->name} updated their purchase request for {$purchaseRequest->product}",
                'type' => 'purchase_request_updated',
                'is_broadcast' => true,
                'extra_data' => [
                    'purchase_request_id' => $purchaseRequest->id,
                    'old_product' => $oldProduct,
                    'new_product' => $purchaseRequest->product,
                    'old_price' => $oldPrice,
                    'new_price' => $purchaseRequest->price
                ]
            ]);

            return Pipeline::success($purchaseRequest);
        } catch (\Exception $e) {
            return Pipeline::error(message: $e->getMessage());
        }
    }

    public function updateStatus(PurchaseRequest $purchaseRequest, array $data): Pipeline
    {
        if ($purchaseRequest->status != PurchaseRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot update non-pending purchase request");
        }

        DB::beginTransaction();
        try {
            $purchaseRequest->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? null,
                'processed_by' => app()->getMessUser()->id,
                'processed_at' => Carbon::now()
            ]);

            $wasApproved = false;
            if ($data['status'] == PurchaseRequestStatus::APPROVED->value) {
                $wasApproved = true;
            }

            if ($wasApproved) {
                // Create purchase record
                $purchaseData = [
                    'date' => $purchaseRequest->date,
                    'mess_user_id' => $purchaseRequest->mess_user_id,
                    'mess_id' => $purchaseRequest->mess_id,
                    'month_id' => $purchaseRequest->month_id,
                    'price' => $purchaseRequest->price,
                    'product' => $purchaseRequest->product,
                ];

                $this->purchaseService->addPurchase($purchaseData);

                // If deposit request is enabled, create a deposit
                if ($purchaseRequest->deposit_request) {
                    $depositData = [
                        'month_id' => $purchaseRequest->month_id,
                        'mess_user_id' => $purchaseRequest->mess_user_id,
                        'mess_id' => $purchaseRequest->mess_id,
                        'amount' => $purchaseRequest->price,
                        'date' => $purchaseRequest->date,
                    ];

                    $this->depositService->addDeposit($depositData);
                }

                // Notify user about approval
                $this->notificationService->sendNotification([
                    'user_id' => $purchaseRequest->messUser->user_id,
                    'title' => 'Purchase Request Approved',
                    'body' => "Your purchase request for {$purchaseRequest->product} has been approved" .
                             ($data['admin_note'] ? ": {$data['admin_note']}" : ""),
                    'type' => 'purchase_request_approved',
                    'extra_data' => [
                        'purchase_request_id' => $purchaseRequest->id,
                        'product' => $purchaseRequest->product,
                        'price' => $purchaseRequest->price,
                        'deposit_created' => $purchaseRequest->deposit_request
                    ]
                ]);
            } else {
                // Notify user about rejection
                $this->notificationService->sendNotification([
                    'user_id' => $purchaseRequest->messUser->user_id,
                    'title' => 'Purchase Request Rejected',
                    'body' => "Your purchase request for {$purchaseRequest->product} has been rejected" .
                             ($data['admin_note'] ? ": {$data['admin_note']}" : ""),
                    'type' => 'purchase_request_rejected',
                    'extra_data' => [
                        'purchase_request_id' => $purchaseRequest->id,
                        'product' => $purchaseRequest->product,
                        'price' => $purchaseRequest->price
                    ]
                ]);
            }

            DB::commit();
            return Pipeline::success($purchaseRequest);
        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: $e->getMessage());
        }
    }

    public function deletePurchaseRequest(PurchaseRequest $purchaseRequest): Pipeline
    {
        if ($purchaseRequest->status != PurchaseRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot delete non-pending purchase request");
        }

        try {
            $purchaseRequest->delete();

            // Notify admins about deletion
            $this->notificationService->sendNotification([
                'title' => 'Purchase Request Deleted',
                'body' => "{$purchaseRequest->messUser->user->name} deleted their purchase request for {$purchaseRequest->product}",
                'type' => 'purchase_request_deleted',
                'is_broadcast' => true,
                'extra_data' => [
                    'product' => $purchaseRequest->product,
                    'price' => $purchaseRequest->price,
                    'user_id' => $purchaseRequest->messUser->user_id,
                    'user_name' => $purchaseRequest->messUser->user->name
                ]
            ]);

            return Pipeline::success(message: "Purchase request deleted successfully");
        } catch (\Exception $e) {
            return Pipeline::error(message: $e->getMessage());
        }
    }

    public function listPurchaseRequests(array $filters = []): Pipeline
    {
        try {
            $query = PurchaseRequest::query()
                ->with(['messUser.user', 'processedBy.user']);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['mess_user_id'])) {
                $query->where('mess_user_id', $filters['mess_user_id']);
            }

            if (isset($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }

            $purchaseRequests = $query->orderBy('created_at', 'desc')->get();

            return Pipeline::success($purchaseRequests);
        } catch (\Exception $e) {
            return Pipeline::error(message: $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Constants\MessPermission;
use App\Enums\ErrorCode;
use App\Facades\Permission;
use App\Helpers\Pipeline;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\PurchaseRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PurchaseRequestController extends Controller
{
    private PurchaseRequestService $purchaseRequestService;

    public function __construct(PurchaseRequestService $purchaseRequestService)
    {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function create(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            "mess_user_id" => [
                "required",
                "numeric",
                new MessUserExistsInCurrentMess(),
                new UserInitiatedInCurrentMonth(),
            ],
            "date" => "required|date",
            "price" => "required|numeric|min:1",
            "product" => "required|string|max:255",
            "product_json" => "sometimes|json|nullable",
            "type" => "sometimes|string",
            "purchase_type" => "required|integer",
            "deposit_request" => "sometimes|boolean",
            "comment" => "sometimes|string|nullable",
        ]);

        // Add additional data
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['mess_id'] = app()->getMess()->id;
        $validatedData['status'] = 0; // Default status (pending)

        // Call the service to create the purchase request
        $pipeline = $this->purchaseRequestService->createPurchaseRequest($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {


        #check if the user has permission to update the purchase request
        #also check if current logged user is the owner of the purchase request

        if (!Permission::canAccessModel($purchaseRequest, "mess_user_id")) {
            # code...
        }


        if (!Permission::canAnyAccessModel([
            MessPermission::PURCHASE_REQUEST_MANAGEMENT,
            MessPermission::PURCHASE_REQUEST_UPDATE,
        ], $purchaseRequest)) {
           return Pipeline::error("You don't have permission to update this purchase request", errorCode: ErrorCode::PERMISSION_DENIED);
        }

        $data = $request->validate([
            "date" => "sometimes|date",
            "price" => "sometimes|numeric|min:1",
            "product" => "sometimes|string|max:255",
            "product_json" => "sometimes|json|nullable",
            "type" => "sometimes|string",
            "purchase_type" => "sometimes|integer",
            "deposit_request" => "sometimes|boolean",
            "comment" => "sometimes|string|nullable",
        ]);

        $pipeline = $this->purchaseRequestService->updatePurchaseRequest($purchaseRequest, $data);

        return $pipeline->toApiResponse();
    }

    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {

        if (!Permission::canAny([
            MessPermission::PURCHASE_REQUEST_MANAGEMENT,
            MessPermission::PURCHASE_REQUEST_UPDATE
        ])) {
           return Pipeline::error(message: "You don't have permission to update this purchase request", errorCode:ErrorCode::PERMISSION_DENIED);
        }

        $data = $request->validate([
            "status" => "required|integer",
            "comment" => "sometimes|string|nullable",
        ]);

        $pipeline = $this->purchaseRequestService->updateStatus($purchaseRequest, $data);

        return $pipeline->toApiResponse();
    }

    public function delete(PurchaseRequest $purchaseRequest)
    {
        // Gate::authorize('delete', $purchaseRequest);
        $pipeline = $this->purchaseRequestService->deletePurchaseRequest($purchaseRequest);

        return $pipeline->toApiResponse();
    }

    public function list(Request $request)
    {
        $filters = $request->validate([
            'status' => 'sometimes|integer',
            'purchase_type' => 'sometimes|integer',
            'deposit_request' => 'sometimes|boolean',
        ]);

        $pipeline = $this->purchaseRequestService->listPurchaseRequests(app()->getMonth(), $filters);

        return $pipeline->toApiResponse();
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $pipeline = $this->purchaseRequestService->getPurchaseRequest($purchaseRequest);

        return $pipeline->toApiResponse();
    }
}

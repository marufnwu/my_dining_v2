<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    private PurchaseService $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function add(Request $request)
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
        ]);

        // Add additional data
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['mess_id'] = app()->getMess()->id;

        // Call the service to add the purchase
        $pipeline = $this->purchaseService->addPurchase($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    public function update(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            "date"=>"sometimes|date",
            "price" => "sometimes|numeric|min:1",
            "product" => "sometimes|string",
        ]);

        $pipeline = $this->purchaseService->updatePurchase($purchase, $data);

        return $pipeline->toApiResponse();
    }

    public function delete(Purchase $purchase)
    {
        // Gate::authorize('delete', $purchase);
        $pipeline = $this->purchaseService->deletePurchase($purchase);

        return $pipeline->toApiResponse();
    }

    public function list()
    {
        $pipeline = $this->purchaseService->listPurchases(app()->getMonth());

        return $pipeline->toApiResponse();
    }
}

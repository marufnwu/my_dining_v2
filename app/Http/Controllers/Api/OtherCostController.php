<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtherCost;
use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\OtherCostService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtherCostController extends Controller
{
    private OtherCostService $otherCostService;

    public function __construct(OtherCostService $otherCostService)
    {
        $this->otherCostService = $otherCostService;
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
            "price" => "required|numeric|min:0",
            "product" => "required|string|max:255",
        ]);

        // Add additional data
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['mess_id'] = app()->getMess()->id;

        // Call the service to add the other cost
        $pipeline = $this->otherCostService->addOtherCost($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    public function update(Request $request, OtherCost $otherCost)
    {
        $data = $request->validate([
            "date" => "sometimes|date",
            "price" => "sometimes|numeric|min:0",
            "product" => "sometimes|string",
        ]);

        $pipeline = $this->otherCostService->updateOtherCost($otherCost, $data);

        return $pipeline->toApiResponse();
    }

    public function delete(OtherCost $otherCost)
    {
        $pipeline = $this->otherCostService->deleteOtherCost($otherCost);

        return $pipeline->toApiResponse();
    }

    public function list()
    {
        $pipeline = $this->otherCostService->listOtherCosts(app()->getMonth());

        return $pipeline->toApiResponse();
    }
}

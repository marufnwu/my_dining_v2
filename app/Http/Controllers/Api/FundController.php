<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fund;
use App\Services\FundService;
use Illuminate\Http\Request;

class FundController extends Controller
{
    private FundService $fundService;

    public function __construct(FundService $fundService)
    {
        $this->fundService = $fundService;
    }

    public function add(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            "date" => "required|date",
            "amount" => "required|numeric|min:0",
            "comment" => "required|string",
        ]);

        // Add additional data
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['mess_id'] = app()->getMess()->id;

        // Call the service to add the fund
        $pipeline = $this->fundService->addFund($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    public function update(Request $request, Fund $fund)
    {
        $data = $request->validate([
            "date" => "sometimes|date",
            "amount" => "sometimes|numeric|min:0",
            "comment" => "nullable|string",
        ]);

        $pipeline = $this->fundService->updateFund($fund, $data);

        return $pipeline->toApiResponse();
    }

    public function delete(Fund $fund)
    {
        $pipeline = $this->fundService->deleteFund($fund);

        return $pipeline->toApiResponse();
    }

    public function list()
    {
        $pipeline = $this->fundService->listFunds(app()->getMonth());

        return $pipeline->toApiResponse();
    }
}

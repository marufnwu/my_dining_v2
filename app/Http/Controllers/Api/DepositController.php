<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\MessUser;
use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    private DepositService $depositService;

    public function __construct(DepositService $depositService)
    {
        $this->depositService = $depositService;
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
            "amount" => "required|numeric|min:0",
        ]);

        // Add additional data
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['mess_id'] = app()->getMess()->id;

        // Call the service to add the deposit
        $pipeline = $this->depositService->addDeposit($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    public function update(Request $request, Deposit $deposit)
    {
        $data = $request->validate([
            "date" => "sometimes|date",
            "amount" => "sometimes|numeric|min:0",
        ]);

        $pipeline = $this->depositService->updateDeposit($deposit, $data);

        return $pipeline->toApiResponse();
    }

    public function delete(Deposit $deposit)
    {
        $pipeline = $this->depositService->deleteDeposit($deposit);

        return $pipeline->toApiResponse();
    }

    public function list()
    {
        $pipeline = $this->depositService->listDeposits(app()->getMonth());

        return $pipeline->toApiResponse();
    }

    public function history(MessUser $messUser)
    {
        $pipeline = $this->depositService->listDepositHistory(app()->getMonth(), $messUser);

        return $pipeline->toApiResponse();
    }
}

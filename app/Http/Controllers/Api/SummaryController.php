<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessUser;
use App\Models\Month;
use App\Services\MessSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(protected MessSummaryService $service)
    {
    }

    public function monthSummary($type)
    {
        $month = app()->getMonth();

        if ($type == "minimal") {
            return $this->service->getMinimalSummary($month)->toApiResponse();

        } else {
            return $this->service->getDetailedSummary($month)->toApiResponse();

        }
    }

    /**
     * Get user summary for a specific month (minimal or detailed)
     *
     * @param Request $request
     * @param Month $month
     * @param ?MessUser $messUser
     * @return JsonResponse
     */
    public function userSummary(Request $request, $type, ?MessUser $messUser = null): JsonResponse
    {
        $validatedData = $request->validate([
            "mess_user_id" => "nullable|numeric|exists:mess_users,id"
        ]);

        $month = app()->getMonth();

        $messUser = $request->input("mess_user_id")
            ? MessUser::find($validatedData["mess_user_id"])
            : auth()->user()->messUser;

        if (!$messUser) {
            return response()->json(["error" => "Invalid MessUser"], 404);
        }

        $isMinimal = $type === 'minimal';
        $response = $isMinimal
            ? $this->service->getUserMinimalSummary($month, $messUser)
            : $this->service->getUserDetailedSummary($month, $messUser);

        return $response->toApiResponse();
    }

}

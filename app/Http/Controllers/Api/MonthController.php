<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateMonthDTO;
use App\Enums\MonthType;
use App\Helpers\Pipeline;
use App\Http\Controllers\Controller;
use App\Services\MonthService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthController extends Controller
{
    private MonthService $monthService;

    public function __construct(MonthService $monthService) {
        $this->monthService = $monthService;
    }
    function createMonth(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:20',
            'type' => 'required|in:' . implode(',', MonthType::values()),
            'month' => 'nullable|integer|between:1,12|required_if:type,automatic', // Month number (1-12)
            'year' => 'nullable|integer|max:'. Carbon::now()->year .'|min:'. Carbon::now()->year .'|required_if:type,automatic', // Year (e.g., 2023)
            'start_at' => 'nullable|date|required_if:type,manual', // Full date for manual type
            "force_close_other"=>"nullable|boolean"
        ]);


        $forceCloseOther = $validated['force_close_other'] ?? false;


        if( !$forceCloseOther  && $this->monthService->hasActiveMonth(app()->getMess())){
            return Pipeline::error("You have already active month. Please close that one and try again!")->toApiResponse();
        }

        // Create DTO from validated data
        $dto = new CreateMonthDTO(
            name: $validated['name'] ?? null,
            type: $validated['type'],
            month: $validated['month'] ?? null,
            year: $validated['year'] ?? null,
            start_at: $validated['start_at'] ?? null,
            force_close_other: $validated['force_close_other'] ?? false,
        );

        // Call the service method
        $pipeline = $this->monthService->createMonth(app()->getMess(), $dto, $forceCloseOther);

        return $pipeline->toApiResponse();
    }

    public function list(){
        return $this->monthService->list()->toApiResponse();
    }

    public function changeStatus(Request $request){
        $validated = $request->validate([
            "status"=>"required|boolean"
        ]);
    }
}

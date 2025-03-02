<?php

namespace App\Http\Controllers\Api;

use App\DTOs\MealDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\MealRequest;
use App\Models\Meal;
use App\Services\MealService;
use App\Services\MessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MealController extends Controller
{
    private MealService $mealService;

    public function __construct(MealService $mealService)
    {
        $this->mealService = $mealService;
    }


    public function add(MealRequest $request)
    {

        $data = $request->validated();
        $data['month_id'] = app()->getMonth()->id;
        $data['mess_id'] = app()->getMess()->id;

        $pipeline = $this->mealService->addMeal($data);

        return $pipeline->toApiResponse();
    }


    public function update(Request $request, Meal $meal)
    {
        $data = $request->validate([
            "breakfast" => "sometimes|numeric|min:0",
            "lunch" => "sometimes|numeric|min:0",
            "dinner" => "sometimes|numeric|min:0",
        ]);
        $data['month_id'] = app()->getMonth()->id;
        $data['mess_id'] = app()->getMess()->id;


        $pipeline = $this->mealService->updateMeal($meal, $data);

        return $pipeline->toApiResponse();
    }


    public function delete(Meal $meal)
    {
        Gate::authorize('delete', $meal);
        // Call the service method
        $pipeline = $this->mealService->deleteMeal($meal);

        // Return API response
        return $pipeline->toApiResponse();
    }


    public function list()
    {
        // Call the service method
        $pipeline = $this->mealService->listMeals();

        // Return API response
        return $pipeline->toApiResponse();
    }
}

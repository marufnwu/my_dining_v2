<?php

namespace App\Http\Controllers\Api;

use App\DTOs\MealDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\MealRequest;
use App\Models\Meal;
use App\Services\MealService;

class MealController extends Controller
{
    private MealService $mealService;

    public function __construct(MealService $mealService)
    {
        $this->mealService = $mealService;
    }

    /**
     * Add a new meal.
     *
     * @param MealRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(MealRequest $request)
    {

        $

        // Create DTO from validated data
        $dto = MealDto::fromArray($request->validated());

        // Call the service method
        $pipeline = $this->mealService->addMeal($dto);

        // Return API response
        return $pipeline->toApiResponse();
    }

    /**
     * Update an existing meal.
     *
     * @param MealRequest $request
     * @param Meal $meal
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(MealRequest $request, Meal $meal)
    {
        // Create DTO from validated data
        $dto = MealDto::fromArray($request->validated());

        // Call the service method
        $pipeline = $this->mealService->updateMeal($meal, $dto);

        // Return API response
        return $pipeline->toApiResponse();
    }

    /**
     * Delete a meal.
     *
     * @param Meal $meal
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Meal $meal)
    {
        // Call the service method
        $pipeline = $this->mealService->deleteMeal($meal);

        // Return API response
        return $pipeline->toApiResponse();
    }

    /**
     * Get a list of meals.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        // Call the service method
        $pipeline = $this->mealService->listMeals();

        // Return API response
        return $pipeline->toApiResponse();
    }
}

<?php

namespace App\Services;

use App\DTOs\MealDto;
use App\Helpers\Pipeline;
use App\Models\Meal;

class MealService
{
    /**
     * Add a new meal.
     *
     * @param MealDto $dto
     * @return Pipeline
     */
    public function addMeal(MealDto $dto): Pipeline
    {
        $meal = Meal::create($dto->toArray());
        return Pipeline::success(data: $meal);
    }

    /**
     * Update an existing meal.
     *
     * @param Meal $meal
     * @param MealDto $dto
     * @return Pipeline
     */
    public function updateMeal(Meal $meal, MealDto $dto): Pipeline
    {
        $meal->update($dto->toArray());
        return Pipeline::success(data: $meal->fresh());
    }

    /**
     * Delete a meal.
     *
     * @param Meal $meal
     * @return Pipeline
     */
    public function deleteMeal(Meal $meal): Pipeline
    {
        $meal->delete();
        return Pipeline::success(message: 'Meal deleted successfully');
    }

    /**
     * Get a list of meals.
     *
     * @return Pipeline
     */
    public function listMeals(): Pipeline
    {
        $meals = Meal::where('mess_id', MessService::currentMess()->id)
            ->orderByDesc('id')
            ->get();
        return Pipeline::success(data: $meals);
    }
}

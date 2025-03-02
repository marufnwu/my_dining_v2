<?php

namespace App\Services;

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
    public function addMeal(array $data): Pipeline
    {
        $meal = Meal::updateOrCreate(
            [
                'month_id' => $data['month_id'],
                'date' => $data['date'],
                'mess_user_id' => $data['mess_user_id'],
                'mess_id' => $data['mess_id'],
            ],
            [
                'breakfast' => $data['breakfast'] ?? 0,
                'lunch' => $data['lunch'] ?? 0,
                'dinner' => $data['dinner'] ?? 0,
            ]
        );
        return Pipeline::success(data: $meal);
    }

    /**
     * Update an existing meal.
     *
     * @param Meal $meal
     * @param MealDto $dto
     * @return Pipeline
     */
    public function updateMeal(Meal $meal, array $data): Pipeline
    {
        $meal->update($data);
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
        $meals = app()->getMonth()->meals()->groupBy('date')->orderByDesc("date")->get();
        return Pipeline::success(data: $meals);
    }
}

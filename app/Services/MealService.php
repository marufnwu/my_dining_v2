<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Meal;
use App\Models\Month;

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
    public function listMeals(Month $month): Pipeline
    {
        $month = Month::with([
            'meals' => function ($query) {
                $query->with("messUser.user")->whereIn('mess_user_id', function ($subQuery) {
                    $subQuery->select('mess_user_id')
                        ->from('initiate_users')
                        ->whereColumn('initiate_users.month_id', 'meals.month_id');
                });
            }
        ])->find($month->id);

        // Group meals by date (ignoring timestamp) and sum meals per day
        $mealsByDate = $month->meals->groupBy(function ($meal) {
            return $meal->date->toDateString(); // Extract only YYYY-MM-DD
        })->map(function ($meals, $date) {
            return [
            'date' => $date,
            'meals' => $meals->map(function ($meal) {
                return $meal;
            }),
            'total_meals' => [
                'total_breakfast' => $meals->sum('breakfast'),
                'total_lunch' => $meals->sum('lunch'),
                'total_dinner' => $meals->sum('dinner'),
            ]
            ];
        })->values()->all();

        // Calculate overall totals
        $totalBreakfast = $month->meals->sum('breakfast');
        $totalLunch = $month->meals->sum('lunch');
        $totalDinner = $month->meals->sum('dinner');

        $data = [
            'meals_by_date' => $mealsByDate,
            'overall_totals' => [
                'total_breakfast' => $totalBreakfast,
                'total_lunch' => $totalLunch,
                'total_dinner' => $totalDinner,
                'total_meals' => $totalBreakfast + $totalLunch + $totalDinner
            ]
        ];

        // $meals = app()->getMonth()->meals()->groupBy('date')->orderByDesc("date")->get();
        return Pipeline::success(data: $data);
    }

    function getUserMealByDate(Month $month, $messUserId, $date): Pipeline
    {
        $meal = $month->meals()->where("date", $date)->where("mess_user_id", $messUserId)->first();
        return Pipeline::success($meal);
    }
}

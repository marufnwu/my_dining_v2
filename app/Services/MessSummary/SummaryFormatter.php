<?php

namespace App\Services\MessSummary;

use App\Models\Month;
use App\Models\MessUser;

class SummaryFormatter
{
    /**
     * Format minimal month summary data
     */
    public function formatMinimalMonthSummary(Month $month, array $coreData): array
    {
        return [
            'month' => $month,
            'total_meal' => $coreData['meals']['total'],
            'total_deposit' => $coreData['total_deposit'],
            'total_purchase' => $coreData['total_purchase'],
            'total_other_cost' => $coreData['total_other_cost'],
            'total_cost' => $coreData['total_cost'],
            'meal_rate' => round($coreData['meal_rate'], 2),
            'per_person_other_cost' => round($coreData['per_person_other_cost'], 2),
            'status' => $coreData['status'],
        ];
    }

    /**
     * Format detailed month summary data
     */
    public function formatDetailedMonthSummary(
        Month $month,
        array $coreData,
        array $userSummaries,
    ): array {
        return [
            'month' => $month,
            'meal_summary' => [
                'breakfast' => $coreData['meals']['breakfast'],
                'lunch' => $coreData['meals']['lunch'],
                'dinner' => $coreData['meals']['dinner'],
                'total' => $coreData['meals']['total'],
            ],
            'cost_summary' => [
                'total_deposit' => $coreData['total_deposit'],
                'total_purchase' => $coreData['total_purchase'],
                'total_other_cost' => $coreData['total_other_cost'],
                'total_cost' => $coreData['total_cost'],
            ],
            'meal_rate' => round($coreData['meal_rate'], 2),
            'per_person_other_cost' => round($coreData['per_person_other_cost'], 2),
            'status' => $coreData['status'],
            'user_summary' => $userSummaries,
        ];
    }

    /**
     * Format minimal user summary data
     */
    public function formatMinimalUserSummary(Month $month, MessUser $messUser, array $userData): array
    {
        return [
            'user' => [
                'id' => $messUser->id,
                'name' => $messUser->user->name ?? 'Unknown',
            ],
            'month_id' => $month->id,
            'total_meal' => $userData['meal_data']['summary']['total'],
            'deposit' => $userData['deposit'],
            'meal_charge' => round($userData['meal_charge'], 2),
            'other_cost_share' => round($userData['other_cost_share'], 2),
            'total_cost' => round($userData['total_cost'], 2),
            'balance' => round($userData['balance'], 2),
            'due' => round($userData['due'], 2),
            'status' => $userData['status'],
            'meal_rate' => round($userData['meal_rate'] ?? 0, 2),
        ];
    }

    /**
     * Format detailed user summary data
     */
    public function formatDetailedUserSummary(Month $month, MessUser $messUser, array $userData, array $coreMonthData): array
    {
        $userMealData = $userData['meal_data']['summary'];

        return [
            'month' => [
                'id' => $month->id,
                'name' => $month->name,
                'year' => $month->year,
                'month' => $month->month,
                'status' => $coreMonthData['status'],
            ],
            'user' => [
                'mess_user' => $messUser,
                'user' => $messUser->user,
            ],
            'meal_summary' => [
                'breakfast' => $userMealData['breakfast'],
                'lunch' => $userMealData['lunch'],
                'dinner' => $userMealData['dinner'],
                'total_meal' => $userMealData['total'],
                'meal_percentage' => round($userData['meal_percentage'] ?? 0, 2),
                'history' => $userData['meal_history'] ?? [],
            ],
            'financial_summary' => [
                'deposit' => $userData['deposit'],
                'deposit_percentage' => round($userData['deposit_percentage'] ?? 0, 2),
                'deposit_history' => $userData['deposit_history'] ?? [],
                'meal_charge' => round($userData['meal_charge'], 2),
                'other_cost_share' => round($userData['other_cost_share'], 2),
                'total_cost' => round($userData['total_cost'], 2),
                'balance' => round($userData['balance'], 2),
                'due' => round($userData['due'], 2),
                'status' => $userData['status'],
                'purchases' => $userData['purchases'] ?? [],
                'other_costs' => $userData['other_costs'] ?? [],
                'contribution' => $userData['contribution'] ?? 0,
            ],
            'month_overview' => [
                'total_meals' => $coreMonthData['meals']['total'],
                'meal_rate' => round($coreMonthData['meal_rate'], 2),
                'per_person_other_cost' => round($coreMonthData['per_person_other_cost'], 2),
                'total_deposit' => $coreMonthData['total_deposit'],
                'total_purchase' => $coreMonthData['total_purchase'],
                'total_other_cost' => $coreMonthData['total_other_cost'],
                'total_cost' => $coreMonthData['total_cost'],
            ],
        ];
    }
}

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
            'summary' => [
                'total_meal' => $coreData['meals']['total'],
                'total_deposit' => $coreData['total_deposit'],
                'total_purchase' => $coreData['total_purchase'],
                'total_other_cost' => $coreData['total_other_cost'],
                'total_cost' => $coreData['total_cost'],
                'meal_rate' => round($coreData['meal_rate'], 2),
                'other_cost_share' => round($coreData['per_person_other_cost'], 2),
                'status' => $coreData['status'],
            ],

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
            'summary' => [
                'total_meal' => $coreData['meals']['total'],
                'total_deposit' => $coreData['total_deposit'],
                'total_purchase' => $coreData['total_purchase'],
                'total_other_cost' => $coreData['total_other_cost'],
                'total_cost' => $coreData['total_cost'],
                'meal_rate' => round($coreData['meal_rate'], 2),
                'other_cost_share' => round($coreData['per_person_other_cost'], 2),
                'status' => $coreData['status'],
            ],
            'details' => [
                'meal_summary' => [
                    'breakfast' => $coreData['meals']['breakfast'],
                    'lunch' => $coreData['meals']['lunch'],
                    'dinner' => $coreData['meals']['dinner'],
                ],
                'users' => $userSummaries,
            ],
        ];
    }

    /**
     * Format minimal user summary data
     */
    public function formatMinimalUserSummary(Month $month, MessUser $messUser, array $userData): array
    {
        return [
            'month' => $month,
            'mess_user' => $messUser->load("user"),
            'summary' => [
                'total_meal' => $userData['meal_data']['summary']['total'],
                'deposit' => $userData['deposit'],
                'meal_charge' => round($userData['meal_charge'], 2),
                'other_cost_share' => round($userData['other_cost_share'], 2),
                'total_cost' => round($userData['total_cost'], 2),
                'balance' => round($userData['balance'], 2),
                'due' => round($userData['due'], 2),
                'status' => $userData['status'],
                'meal_rate' => round($userData['meal_rate'] ?? 0, 2),
            ],

        ];
    }

    /**
     * Format detailed user summary data
     */
    public function formatDetailedUserSummary(Month $month, MessUser $messUser, array $userData, array $coreMonthData): array
    {
        $userMealData = $userData['meal_data']['summary'];

        return [
            'month' => $month,
            'mess_user' => $messUser->load("user"),
            'summary' => [
                'total_meal' => $userMealData['total'],
                'deposit' => $userData['deposit'],
                'meal_charge' => round($userData['meal_charge'], 2),
                'other_cost_share' => round($userData['other_cost_share'], 2),
                'total_cost' => round($userData['total_cost'], 2),
                'balance' => round($userData['balance'], 2),
                'due' => round($userData['due'], 2),
                'status' => $userData['status'],
                'meal_rate' => round($coreMonthData['meal_rate'], 2),
            ],
            'details' => [
                'meal_summary' => [
                    'breakfast' => $userMealData['breakfast'],
                    'lunch' => $userMealData['lunch'],
                    'dinner' => $userMealData['dinner'],
                    'percentage' => round($userData['meal_percentage'] ?? 0, 2),
                ],
                'financial_details' => [
                    'deposit_percentage' => round($userData['deposit_percentage'] ?? 0, 2),
                    'purchases' => $userData['purchases'] ?? [],
                    'other_costs' => $userData['other_costs'] ?? [],
                    'contribution' => $userData['contribution'] ?? 0,
                ],
                'history' => [
                    'meals' => $userData['meal_history'] ?? [],
                    'deposits' => $userData['deposit_history'] ?? [],
                ],
            ],
            'month_overview' => [
                'total_meals' => $coreMonthData['meals']['total'],
                'total_deposit' => $coreMonthData['total_deposit'],
                'total_purchase' => $coreMonthData['total_purchase'],
                'total_other_cost' => $coreMonthData['total_other_cost'],
                'total_cost' => $coreMonthData['total_cost'],
            ],
        ];
    }
}

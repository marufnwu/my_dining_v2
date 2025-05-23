<?php

namespace App\Services\MessSummary;

use App\Models\Month;
use App\Models\MessUser;
use App\Services\DepositService;
use App\Services\MealService;
use App\Services\OtherCostService;
use App\Services\PurchaseService;

class DataAggregator
{
    protected $mealService;
    protected $depositService;
    protected $purchaseService;
    protected $otherCostService;

    public function __construct(
        MealService $mealService,
        DepositService $depositService,
        PurchaseService $purchaseService,
        OtherCostService $otherCostService
    ) {
        $this->mealService = $mealService;
        $this->depositService = $depositService;
        $this->purchaseService = $purchaseService;
        $this->otherCostService = $otherCostService;
    }

    /**
     * Get core data for the month summary
     */
    public function getCoreMonthData(Month $month): array
    {
        // Get meal data
        $mealData = $this->mealService->getTotalMeals($month)->data;

        // Get financial data
        $totalDeposit = $this->depositService->getTotalDeposit($month)->data;
        $totalPurchase = $this->purchaseService->getTotalPurchases($month)->data;
        $totalOtherCost = $this->otherCostService->getTotalOtherCosts($month)->data;

        $totalCost = $totalPurchase + $totalOtherCost;
        $totalMeal = $mealData['total'];

        // Calculate meal rate based on purchases only, not including other costs
        $mealRate = $totalMeal > 0 ? $totalPurchase / $totalMeal : 0;

        // Calculate per person other cost
        $totalUsers = $month->initiatedUser()->count();
        $perPersonOtherCost = $totalUsers > 0 ? $totalOtherCost / $totalUsers : 0;

        return [
            'meals' => $mealData,
            'total_deposit' => $totalDeposit,
            'total_purchase' => $totalPurchase,
            'total_other_cost' => $totalOtherCost,
            'total_cost' => $totalCost,
            'meal_rate' => $mealRate,
            'per_person_other_cost' => $perPersonOtherCost,
            'status' => $this->calculateStatus($totalDeposit, $totalCost),
            'total_users' => $totalUsers
        ];
    }

    /**
     * Get core data for a specific user
     */
    public function getCoreUserData(Month $month, MessUser $messUser, float $mealRate): array
    {
        // Get user's meals
        $userMealData = $this->mealService->getUserMeals($month, $messUser)->data;
        $userTotalMeal = $userMealData['summary']['total'];

        // Get user's deposit
        $userDeposit = $this->depositService->getUserDeposit($month, $messUser)->data;

        // Calculate per person other cost
        $totalUsers = $month->initiatedUser()->count();
        $totalOtherCost = $this->otherCostService->getTotalOtherCosts($month)->data;
        $perPersonOtherCost = $totalUsers > 0 ? $totalOtherCost / $totalUsers : 0;

        // Calculate user's financial data - meal charge is based only on purchases now
        $userMealCharge = $userTotalMeal * $mealRate;

        // Total cost includes both meal charge and per person other cost
        $userTotalCost = $userMealCharge + $perPersonOtherCost;

        // Balance and due calculation
        $userBalance = $userDeposit - $userTotalCost;
        $userDue = $userBalance < 0 ? abs($userBalance) : 0;

        return [
            'meal_data' => $userMealData,
            'deposit' => $userDeposit,
            'meal_charge' => $userMealCharge,
            'other_cost_share' => $perPersonOtherCost,
            'total_cost' => $userTotalCost,
            'balance' => $userBalance,
            'due' => $userDue,
            'status' => $userBalance >= 0 ? 'positive' : 'negative'
        ];
    }

    /**
     * Get detailed data for a specific user
     */
    public function getDetailedUserData(Month $month, MessUser $messUser, float $mealRate): array
    {
        // Get core user data
        $coreUserData = $this->getCoreUserData($month, $messUser, $mealRate);

        // Get additional data
        $userMealData = $coreUserData['meal_data'];
        $userTotalMeal = $userMealData['summary']['total'];
        $userDeposit = $coreUserData['deposit'];

        // Calculate user's contribution percentage
        $totalMeal = $this->mealService->getTotalMeals($month)->data['total'];
        $totalDeposit = $this->depositService->getTotalDeposit($month)->data;

        $mealPercentage = $totalMeal > 0 ? ($userTotalMeal / $totalMeal) * 100 : 0;
        $depositPercentage = $totalDeposit > 0 ? ($userDeposit / $totalDeposit) * 100 : 0;

        // Get user's purchases and other costs
        $userPurchases = $month->purchases()->where('mess_user_id', $messUser->id)->get();
        $userPurchaseTotal = $userPurchases->sum('price');

        $userOtherCosts = $month->otherCosts()->where('mess_user_id', $messUser->id)->get();
        $userOtherCostTotal = $userOtherCosts->sum('price');



        // Get user's meal and deposit history
        $userMealHistory = $month->meals()
            ->where('mess_user_id', $messUser->id)
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        $userDepositHistory = $month->deposits()
            ->where('mess_user_id', $messUser->id)
            ->orderBy('date', 'desc')
            ->get();

        return array_merge($coreUserData, [
            'meal_percentage' => $mealPercentage,
            'deposit_percentage' => $depositPercentage,
            'purchases' => [
                'total' => $userPurchaseTotal,
                'recent' => $userPurchases->take(5),
            ],
            'other_costs' => [
                'total' => $userOtherCostTotal,
                'recent' => $userOtherCosts->take(5),
            ],
            'contribution' => $userPurchaseTotal + $userOtherCostTotal,
            'meal_history' => $userMealHistory,
            'deposit_history' => $userDepositHistory
        ]);
    }

    /**
     * Get summary data for all users in a month
     */
    public function getUserSummaries(Month $month, float $mealRate): array
    {
        $initiatedUsers = $month->initiatedUser()->with('messUser.user')->get();
        $userSummaries = [];

        foreach ($initiatedUsers as $initiatedUser) {
            $messUser = $initiatedUser->messUser;

            if (!$messUser) {
                continue; // Skip if there's no associated MessUser
            }

            // Get core user data
            $userData = $this->getCoreUserData($month, $messUser, $mealRate);
            $userMealData = $userData['meal_data'];

            $userSummaries[] = [
                'mess_user' => $messUser,
                'user' => $messUser->user ?? null,
                'meals' => [
                    'breakfast' => $userMealData['summary']['breakfast'],
                    'lunch' => $userMealData['summary']['lunch'],
                    'dinner' => $userMealData['summary']['dinner'],
                    'total' => $userMealData['summary']['total'],
                ],
                'deposit' => $userData['deposit'],
                'meal_charge' => round($userData['meal_charge'], 2),
                'other_cost_share' => round($userData['other_cost_share'], 2),
                'total_cost' => round($userData['total_cost'], 2),
                'balance' => round($userData['balance'], 2),
                'due' => round($userData['due'], 2),
                'status' => $userData['status'],
            ];
        }

        return $userSummaries;
    }

    /**
     * Calculate status based on deposit and cost
     */
    protected function calculateStatus($totalDeposit, $totalCost): string
    {
        if ($totalDeposit > $totalCost) {
            return 'surplus';
        } elseif ($totalDeposit < $totalCost) {
            return 'deficit';
        } else {
            return 'balanced';
        }
    }
}

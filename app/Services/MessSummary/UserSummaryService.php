<?php

namespace App\Services\MessSummary;

use App\Helpers\Pipeline;
use App\Models\Month;
use App\Models\MessUser;
use App\Services\DepositService;
use App\Services\MealService;
use App\Services\OtherCostService;
use App\Services\PurchaseService;

class UserSummaryService
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

    public function getMinimal(Month $month, MessUser $messUser): Pipeline
    {
        // Get core data
        $aggregator = new DataAggregator(
            $this->mealService,
            $this->depositService,
            $this->purchaseService,
            $this->otherCostService
        );
        
        $coreData = $aggregator->getCoreMonthData($month);
        $userData = $aggregator->getCoreUserData($month, $messUser, $coreData['meal_rate']);
        
        // Format data
        $formatter = new SummaryFormatter();
        $data = $formatter->formatMinimalUserSummary($month, $messUser, $userData);
        
        return Pipeline::success(data: $data);
    }

    public function getDetailed(Month $month, MessUser $messUser): Pipeline
    {
        // Get core data
        $aggregator = new DataAggregator(
            $this->mealService,
            $this->depositService,
            $this->purchaseService,
            $this->otherCostService
        );
        
        $coreData = $aggregator->getCoreMonthData($month);
        $userData = $aggregator->getDetailedUserData($month, $messUser, $coreData['meal_rate']);
        
        // Format data
        $formatter = new SummaryFormatter();
        $data = $formatter->formatDetailedUserSummary($month, $messUser, $userData, $coreData);
        
        return Pipeline::success(data: $data);
    }
    
    // Backward compatibility
    public function getUserSpecificSummary(Month $month, MessUser $messUser): Pipeline
    {
        return $this->getDetailed($month, $messUser);
    }
}
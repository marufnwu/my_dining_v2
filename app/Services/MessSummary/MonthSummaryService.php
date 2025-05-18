<?php

namespace App\Services\MessSummary;

use App\Helpers\Pipeline;
use App\Models\Month;
use App\Services\DepositService;
use App\Services\MealService;
use App\Services\OtherCostService;
use App\Services\PurchaseService;

class MonthSummaryService
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

    public function getMinimal(Month $month): Pipeline
    {
        // Get core data
        $aggregator = new DataAggregator(
            $this->mealService,
            $this->depositService,
            $this->purchaseService,
            $this->otherCostService
        );

        $coreData = $aggregator->getCoreMonthData($month);

        // Format data
        $formatter = new SummaryFormatter();
        $data = $formatter->formatMinimalMonthSummary($month, $coreData);

        return Pipeline::success(data: $data);
    }

    public function getDetailed(Month $month): Pipeline
    {
        // Get core and detailed data
        $aggregator = new DataAggregator(
            $this->mealService,
            $this->depositService,
            $this->purchaseService,
            $this->otherCostService
        );

        $coreData = $aggregator->getCoreMonthData($month);
        $userSummaries = $aggregator->getUserSummaries($month, $coreData['meal_rate']);

        // Format data
        $formatter = new SummaryFormatter();
        $data = $formatter->formatDetailedMonthSummary(
            $month,
            $coreData,
            $userSummaries,
        );

        return Pipeline::success(data: $data);
    }

    // Backward compatibility
    public function get(Month $month): Pipeline
    {
        return $this->getDetailed($month);
    }
}

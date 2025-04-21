<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Month;

class MessSummaryService
{
    function get(Month $month) : Pipeline {

        $totalBreakFastMeal = $month->meals()->sum("brealfast");
        $totalLunchMeal = $month->meals()->sum("lunch");
        $totalDinnerMeal = $month->meals()->sum("dinner");

        $totalMeal = $totalBreakFastMeal + $totalLunchMeal + $totalDinnerMeal;

        $toalDeposit = $month->deposits()->sum("amount");

        $totalPurchase = $month->purchases()->sum("price");

        $totalOtherCost = $month->otherC

    }
}

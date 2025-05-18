<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Month;
use App\Models\MessUser;
use App\Services\MessSummary\MonthSummaryService;
use App\Services\MessSummary\UserSummaryService;

class MessSummaryService
{
    protected $monthSummaryService;
    protected $userSummaryService;

    public function __construct(
        MonthSummaryService $monthSummaryService,
        UserSummaryService $userSummaryService
    ) {
        $this->monthSummaryService = $monthSummaryService;
        $this->userSummaryService = $userSummaryService;
    }

    /**
     * Get minimal summary for a month
     */
    function getMinimalSummary(Month $month): Pipeline
    {
        return $this->monthSummaryService->getMinimal($month);
    }

    /**
     * Get detailed summary for a month
     */
    function getDetailedSummary(Month $month): Pipeline
    {
        return $this->monthSummaryService->getDetailed($month);
    }

    /**
     * For backward compatibility
     */
    function get(Month $month): Pipeline
    {
        return $this->getDetailedSummary($month);
    }

    /**
     * Get minimal summary for a specific user in a month
     */
    function getUserMinimalSummary(Month $month, MessUser $messUser): Pipeline
    {
        return $this->userSummaryService->getMinimal($month, $messUser);
    }

    /**
     * Get detailed summary for a specific user in a month
     */
    function getUserDetailedSummary(Month $month, MessUser $messUser): Pipeline
    {
        return $this->userSummaryService->getDetailed($month, $messUser);
    }

    /**
     * For backward compatibility with user specific summary
     */
    function getUserSpecificSummary(Month $month, MessUser $messUser): Pipeline
    {
        return $this->getUserDetailedSummary($month, $messUser);
    }
}

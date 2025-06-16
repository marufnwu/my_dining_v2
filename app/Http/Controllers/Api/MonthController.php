<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateMonthDTO;
use App\Enums\MonthType;
use App\Helpers\Pipeline;
use App\Http\Controllers\Controller;
use App\Services\MonthService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthController extends Controller
{
    private MonthService $monthService;

    public function __construct(MonthService $monthService) {
        $this->monthService = $monthService;
    }
    function createMonth(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:20',
            'type' => 'required|in:' . implode(',', MonthType::values()),
            'month' => 'nullable|integer|between:1,12|required_if:type,automatic', // Month number (1-12)
            'year' => 'nullable|integer|max:'. Carbon::now()->year .'|min:'. Carbon::now()->year .'|required_if:type,automatic', // Year (e.g., 2023)
            'start_at' => 'nullable|date|required_if:type,manual', // Full date for manual type
            "force_close_other"=>"nullable|boolean"
        ]);


        $forceCloseOther = $validated['force_close_other'] ?? false;


        if( !$forceCloseOther  && $this->monthService->hasActiveMonth(app()->getMess())){
            return Pipeline::error("You have already active month. Please close that one and try again!")->toApiResponse();
        }

        // Create DTO from validated data
        $dto = new CreateMonthDTO(
            name: $validated['name'] ?? null,
            type: $validated['type'],
            month: $validated['month'] ?? null,
            year: $validated['year'] ?? null,
            start_at: $validated['start_at'] ?? null,
            force_close_other: $validated['force_close_other'] ?? false,
        );

        // Call the service method
        $pipeline = $this->monthService->createMonth(app()->getMess(), $dto, $forceCloseOther);

        return $pipeline->toApiResponse();
    }

    public function list(){
        return $this->monthService->list()->toApiResponse();
    }

    public function changeStatus(Request $request){
        $validated = $request->validate([
            "status"=>"required|boolean"
        ]);

        $month = app()->getMonth();
        if (!$month) {
            return Pipeline::error("No active month found")->toApiResponse();
        }

        $pipeline = $this->monthService->changeStatus($month, $validated['status']);
        return $pipeline->toApiResponse();
    }

    /**
     * Get detailed month information with statistics
     */
    public function show(Request $request, $monthId = null)
    {
        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $pipeline = $this->monthService->getMonthDetails($month);
        return $pipeline->toApiResponse();
    }

    /**
     * Get month summary with financial and meal statistics
     */
    public function summary(Request $request, $monthId = null)
    {
        $validated = $request->validate([
            'include_user_details' => 'nullable|boolean',
            'include_daily_breakdown' => 'nullable|boolean',
        ]);

        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $includeUserDetails = $validated['include_user_details'] ?? false;
        $includeDailyBreakdown = $validated['include_daily_breakdown'] ?? false;

        $pipeline = $this->monthService->getMonthSummary($month, $includeUserDetails, $includeDailyBreakdown);
        return $pipeline->toApiResponse();
    }

    /**
     * Close current month and optionally create next month
     */
    public function closeMonth(Request $request)
    {
        $validated = $request->validate([
            'create_next_month' => 'nullable|boolean',
            'next_month_type' => 'nullable|in:' . implode(',', MonthType::values()),
            'next_month_name' => 'nullable|string|max:20',
        ]);

        $currentMonth = app()->getMonth();
        if (!$currentMonth) {
            return Pipeline::error("No active month to close")->toApiResponse();
        }

        $createNext = $validated['create_next_month'] ?? false;
        $nextMonthType = $validated['next_month_type'] ?? MonthType::AUTOMATIC->value;
        $nextMonthName = $validated['next_month_name'] ?? null;

        $pipeline = $this->monthService->closeCurrentMonth($currentMonth, $createNext, $nextMonthType, $nextMonthName);
        return $pipeline->toApiResponse();
    }

    /**
     * Duplicate month structure (copy initiated users to new month)
     */
    public function duplicate(Request $request, $monthId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'type' => 'required|in:' . implode(',', MonthType::values()),
            'month' => 'nullable|integer|between:1,12|required_if:type,automatic',
            'year' => 'nullable|integer|min:' . Carbon::now()->year . '|required_if:type,automatic',
            'start_at' => 'nullable|date|required_if:type,manual',
            'copy_initiated_users' => 'nullable|boolean',
        ]);

        $sourceMonth = MonthService::getSelectedMonth($monthId);
        if (!$sourceMonth) {
            return Pipeline::error("Source month not found")->toApiResponse();
        }

        $copyUsers = $validated['copy_initiated_users'] ?? true;

        $dto = new CreateMonthDTO(
            name: $validated['name'],
            type: $validated['type'],
            month: $validated['month'] ?? null,
            year: $validated['year'] ?? null,
            start_at: $validated['start_at'] ?? null,
            force_close_other: true
        );

        $pipeline = $this->monthService->duplicateMonth($sourceMonth, $dto, $copyUsers);
        return $pipeline->toApiResponse();
    }

    /**
     * Get month comparison between two months
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'month1_id' => 'required|integer|exists:months,id',
            'month2_id' => 'required|integer|exists:months,id',
            'comparison_type' => 'nullable|in:financial,meals,users,all',
        ]);

        $month1 = MonthService::getSelectedMonth($validated['month1_id']);
        $month2 = MonthService::getSelectedMonth($validated['month2_id']);

        if (!$month1 || !$month2) {
            return Pipeline::error("One or both months not found")->toApiResponse();
        }

        $comparisonType = $validated['comparison_type'] ?? 'all';

        $pipeline = $this->monthService->compareMonths($month1, $month2, $comparisonType);
        return $pipeline->toApiResponse();
    }

    /**
     * Get month statistics over time
     */
    public function statistics(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|in:last_3_months,last_6_months,last_year,all',
            'metrics' => 'nullable|array',
            'metrics.*' => 'in:total_deposits,total_expenses,total_meals,user_count,avg_meal_cost',
        ]);

        $period = $validated['period'] ?? 'last_6_months';
        $metrics = $validated['metrics'] ?? ['total_deposits', 'total_expenses', 'total_meals'];

        $pipeline = $this->monthService->getStatistics(app()->getMess(), $period, $metrics);
        return $pipeline->toApiResponse();
    }

    /**
     * Export month data
     */
    public function export(Request $request, $monthId = null)
    {
        $validated = $request->validate([
            'format' => 'nullable|in:json,csv,excel',
            'include_details' => 'nullable|boolean',
            'sections' => 'nullable|array',
            'sections.*' => 'in:meals,deposits,purchases,other_costs,funds,summary',
        ]);

        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $format = $validated['format'] ?? 'json';
        $includeDetails = $validated['include_details'] ?? true;
        $sections = $validated['sections'] ?? ['meals', 'deposits', 'purchases', 'other_costs', 'summary'];

        $pipeline = $this->monthService->exportMonth($month, $format, $includeDetails, $sections);
        return $pipeline->toApiResponse();
    }

    /**
     * Get month activity timeline
     */
    public function timeline(Request $request, $monthId = null)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'activity_types' => 'nullable|array',
            'activity_types.*' => 'in:meals,deposits,purchases,other_costs,user_actions',
            'user_id' => 'nullable|integer|exists:mess_users,id',
        ]);

        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : $month->start_at;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : ($month->end_at ?? Carbon::now());
        $activityTypes = $validated['activity_types'] ?? ['meals', 'deposits', 'purchases', 'other_costs'];
        $userId = $validated['user_id'] ?? null;

        $pipeline = $this->monthService->getActivityTimeline($month, $startDate, $endDate, $activityTypes, $userId);
        return $pipeline->toApiResponse();
    }

    /**
     * Get budget analysis for the month
     */
    public function budgetAnalysis(Request $request, $monthId = null)
    {
        $validated = $request->validate([
            'budget_amount' => 'nullable|numeric|min:0',
            'category_budgets' => 'nullable|array',
            'category_budgets.groceries' => 'nullable|numeric|min:0',
            'category_budgets.utilities' => 'nullable|numeric|min:0',
            'category_budgets.maintenance' => 'nullable|numeric|min:0',
        ]);

        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $budgetAmount = $validated['budget_amount'] ?? null;
        $categoryBudgets = $validated['category_budgets'] ?? [];

        $pipeline = $this->monthService->getBudgetAnalysis($month, $budgetAmount, $categoryBudgets);
        return $pipeline->toApiResponse();
    }

    /**
     * Validate month data integrity
     */
    public function validate(Request $request, $monthId = null)
    {
        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $pipeline = $this->monthService->validateMonthData($month);
        return $pipeline->toApiResponse();
    }

    /**
     * Get month performance metrics
     */
    public function performance(Request $request, $monthId = null)
    {
        $validated = $request->validate([
            'compare_with_previous' => 'nullable|boolean',
            'include_trends' => 'nullable|boolean',
        ]);

        $month = $monthId ?
            MonthService::getSelectedMonth($monthId) :
            app()->getMonth();

        if (!$month) {
            return Pipeline::error("Month not found")->toApiResponse();
        }

        $compareWithPrevious = $validated['compare_with_previous'] ?? true;
        $includeTrends = $validated['include_trends'] ?? true;

        $pipeline = $this->monthService->getPerformanceMetrics($month, $compareWithPrevious, $includeTrends);
        return $pipeline->toApiResponse();
    }
}

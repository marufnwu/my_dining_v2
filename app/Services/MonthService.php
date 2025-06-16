<?php

namespace App\Services;

use App\DTOs\CreateMonthDTO;
use App\Enums\MessStatus;
use App\Enums\MonthType;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\Month;
use App\Models\Fund;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MonthService
{
    // Add your service methods here


    public static function getSelectedMonth($monthId): ?Month
    {
        return MessService::currentMess()?->months()->where("id", $monthId)->first() ?? null;
    }

    public static function isUserInitiatedInCurrentMonth($userId): bool
    {
        return app()->getMonth()->initiatedUser()->where("mess_user_id", $userId)->exists() ?? false;
    }

    public function createMonth(Mess $mess, CreateMonthDTO $dto, bool $forceCloseOthers = false): Pipeline
    {

        if ($forceCloseOthers) {
            $this->closeMonths();
        }

        $data = [
            'name' => $dto->name,
            'type' => $dto->type,
            'month' => $dto->month,
            'year' => $dto->year,
            'start_at' => $dto->start_at,
        ];

        if ($data['type'] === MonthType::AUTOMATIC->value) {
            $month = $data['month'];
            $year = $data['year'];

            $data['start_at'] = Carbon::create($year, $month, 1)->startOfMonth();
            $data['end_at'] = Carbon::create($year, $month, 1)->endOfMonth();
        } elseif ($data['type'] === MonthType::MANUAL->value) {
            $data['start_at'] = Carbon::parse($data['start_at'])->startOfMonth();
        }

        unset($data['month'], $data['year']);

        $currentMess = $mess;

        if ($currentMess->status == MessStatus::ACTIVE->value) {
            return Pipeline::error("You must have to close you current mess");
        }

        $data['mess_id'] = $currentMess->id;
        // Create the month
        $month = Month::create($data);

        return Pipeline::success(data: $month);
    }

    function list(): Pipeline
    {
        $months = MessService::currentMess()->months()->orderByDesc("id")->get();
        return Pipeline::success($months);
    }

    function hasActiveMonth(Mess $mess): bool
    {
        return $mess->months->contains(function ($month) {
            return $month->is_active;
        });
    }

    function changeStatus(Month $month, $status): Pipeline
    {
        $month->status = $status;
        return Pipeline::success($month, "Month updated successfully");
    }

    function closeMonths(?Collection $months = null): bool
    {
        $query = Month::query();

        if ($months) {
            $query->whereIn('id', $months->pluck('id'));
        }

        $query->where(function ($q) {
            $q->whereNull('end_at')
                ->orWhere('end_at', '>', Carbon::now());
        })->update(['end_at' => Carbon::now()]);

        return true;
    }

    /**
     * Get detailed month information with statistics
     */
    public function getMonthDetails(Month $month): Pipeline
    {
        $month->load([
            'initiatedUser.messUser.user',
            'meals.messUser.user',
            'deposits.messUser.user',
            'purchases.messUser.user',
            'otherCosts.messUser.user',
            'mess'
        ]);

        $details = [
            'month' => $month,
            'user_count' => $month->initiatedUser->count(),
            'total_meals' => [
                'breakfast' => $month->meals->sum('breakfast'),
                'lunch' => $month->meals->sum('lunch'),
                'dinner' => $month->meals->sum('dinner'),
            ],
            'financial_summary' => [
                'total_deposits' => $month->deposits->sum('amount'),
                'total_purchases' => $month->purchases->sum('price'),
                'total_other_costs' => $month->otherCosts->sum('price'),
                'balance' => $month->deposits->sum('amount') - $month->purchases->sum('price') - $month->otherCosts->sum('price'),
            ],
            'recent_activities' => [
                'latest_meals' => $month->meals()->latest()->limit(10)->get(),
                'latest_deposits' => $month->deposits()->latest()->limit(5)->get(),
                'latest_purchases' => $month->purchases()->latest()->limit(5)->get(),
            ]
        ];

        return Pipeline::success($details, "Month details retrieved successfully");
    }

    /**
     * Get comprehensive month summary
     */
    public function getMonthSummary(Month $month, bool $includeUserDetails = false, bool $includeDailyBreakdown = false): Pipeline
    {
        $summary = [
            'month_info' => [
                'id' => $month->id,
                'name' => $month->name,
                'type' => $month->type,
                'start_date' => $month->start_at,
                'end_date' => $month->end_at,
                'is_active' => $month->is_active,
            ],
            'financial_summary' => [
                'total_deposits' => $month->deposits->sum('amount'),
                'total_purchases' => $month->purchases->sum('price'),
                'total_other_costs' => $month->otherCosts->sum('price'),
                'net_balance' => $month->deposits->sum('amount') - $month->purchases->sum('price') - $month->otherCosts->sum('price'),
            ],
            'meal_summary' => [
                'total_breakfast' => $month->meals->sum('breakfast'),
                'total_lunch' => $month->meals->sum('lunch'),
                'total_dinner' => $month->meals->sum('dinner'),
                'total_meals' => $month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner'),
            ],
            'user_summary' => [
                'total_users' => $month->initiatedUser->count(),
                'active_users' => $month->initiatedUser->where('active', true)->count(),
            ]
        ];

        if ($includeUserDetails) {
            $summary['user_details'] = $month->initiatedUser->map(function ($initUser) use ($month) {
                $messUser = $initUser->messUser;
                return [
                    'user_id' => $messUser->user->id,
                    'name' => $messUser->user->name,
                    'total_deposits' => $month->deposits->where('mess_user_id', $messUser->id)->sum('amount'),
                    'total_meals' => $month->meals->where('mess_user_id', $messUser->id)->count(),
                    'meal_cost_breakdown' => [
                        'breakfast' => $month->meals->where('mess_user_id', $messUser->id)->sum('breakfast'),
                        'lunch' => $month->meals->where('mess_user_id', $messUser->id)->sum('lunch'),
                        'dinner' => $month->meals->where('mess_user_id', $messUser->id)->sum('dinner'),
                    ]
                ];
            });
        }

        if ($includeDailyBreakdown) {
            $summary['daily_breakdown'] = $this->getDailyBreakdown($month);
        }

        return Pipeline::success($summary, "Month summary retrieved successfully");
    }

    /**
     * Close current month and optionally create next month
     */
    public function closeCurrentMonth(Month $currentMonth, bool $createNext = false, string $nextMonthType = 'automatic', string $nextMonthName = null): Pipeline
    {
        // Close the current month
        $currentMonth->update(['end_at' => Carbon::now()]);

        $result = ['closed_month' => $currentMonth];

        if ($createNext) {
            $nextMonthData = $this->prepareNextMonthData($currentMonth, $nextMonthType, $nextMonthName);

            $nextDto = new CreateMonthDTO(
                name: $nextMonthData['name'],
                type: $nextMonthData['type'],
                month: $nextMonthData['month'],
                year: $nextMonthData['year'],
                start_at: $nextMonthData['start_at'],
                force_close_other: false
            );

            $nextMonthResult = $this->createMonth($currentMonth->mess()->first(), $nextDto, false);

            if ($nextMonthResult->isSuccess()) {
                $result['next_month'] = $nextMonthResult->getData();
            } else {
                return Pipeline::error("Month closed but failed to create next month");
            }
        }

        return Pipeline::success($result, "Month closed successfully" . ($createNext ? " and next month created" : ""));
    }

    /**
     * Duplicate month structure
     */
    public function duplicateMonth(Month $sourceMonth, CreateMonthDTO $dto, bool $copyInitiatedUsers = true): Pipeline
    {
        // Create new month
        $newMonthResult = $this->createMonth($sourceMonth->mess()->first(), $dto, true);

        if (!$newMonthResult->isSuccess()) {
            return $newMonthResult;
        }

        $newMonth = $newMonthResult->getData();

        if ($copyInitiatedUsers) {
            // Copy initiated users from source month
            $sourceMonth->initiatedUser->each(function ($initUser) use ($newMonth) {
                $newMonth->initiatedUser()->create([
                    'mess_user_id' => $initUser->mess_user_id,
                    'mess_id' => $initUser->mess_id,
                    'active' => true,
                ]);
            });
        }

        return Pipeline::success($newMonth, "Month duplicated successfully");
    }

    /**
     * Compare two months
     */
    public function compareMonths(Month $month1, Month $month2, string $comparisonType = 'all'): Pipeline
    {
        $comparison = [
            'month1' => [
                'id' => $month1->id,
                'name' => $month1->name,
                'period' => $month1->start_at->format('Y-m-d') . ' to ' . ($month1->end_at ? $month1->end_at->format('Y-m-d') : 'ongoing'),
            ],
            'month2' => [
                'id' => $month2->id,
                'name' => $month2->name,
                'period' => $month2->start_at->format('Y-m-d') . ' to ' . ($month2->end_at ? $month2->end_at->format('Y-m-d') : 'ongoing'),
            ]
        ];

        if (in_array($comparisonType, ['financial', 'all'])) {
            $comparison['financial_comparison'] = [
                'deposits' => [
                    'month1' => $month1->deposits->sum('amount'),
                    'month2' => $month2->deposits->sum('amount'),
                    'difference' => $month1->deposits->sum('amount') - $month2->deposits->sum('amount'),
                ],
                'expenses' => [
                    'month1' => $month1->purchases->sum('price') + $month1->otherCosts->sum('price'),
                    'month2' => $month2->purchases->sum('price') + $month2->otherCosts->sum('price'),
                    'difference' => ($month1->purchases->sum('price') + $month1->otherCosts->sum('price')) - ($month2->purchases->sum('price') + $month2->otherCosts->sum('price')),
                ]
            ];
        }

        if (in_array($comparisonType, ['meals', 'all'])) {
            $comparison['meal_comparison'] = [
                'total_meals' => [
                    'month1' => $month1->meals->sum('breakfast') + $month1->meals->sum('lunch') + $month1->meals->sum('dinner'),
                    'month2' => $month2->meals->sum('breakfast') + $month2->meals->sum('lunch') + $month2->meals->sum('dinner'),
                ],
                'breakdown' => [
                    'breakfast' => [
                        'month1' => $month1->meals->sum('breakfast'),
                        'month2' => $month2->meals->sum('breakfast'),
                    ],
                    'lunch' => [
                        'month1' => $month1->meals->sum('lunch'),
                        'month2' => $month2->meals->sum('lunch'),
                    ],
                    'dinner' => [
                        'month1' => $month1->meals->sum('dinner'),
                        'month2' => $month2->meals->sum('dinner'),
                    ]
                ]
            ];
        }

        if (in_array($comparisonType, ['users', 'all'])) {
            $comparison['user_comparison'] = [
                'initiated_users' => [
                    'month1' => $month1->initiatedUser->count(),
                    'month2' => $month2->initiatedUser->count(),
                    'difference' => $month1->initiatedUser->count() - $month2->initiatedUser->count(),
                ]
            ];
        }

        return Pipeline::success($comparison, "Month comparison completed successfully");
    }

    /**
     * Get statistics over time
     */
    public function getStatistics(Mess $mess, string $period = 'last_6_months', array $metrics = []): Pipeline
    {
        $months = $this->getMonthsByPeriod($mess, $period);

        $statistics = [
            'period' => $period,
            'month_count' => $months->count(),
            'date_range' => [
                'start' => $months->min('start_at'),
                'end' => $months->max('end_at') ?? Carbon::now(),
            ]
        ];

        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'total_deposits':
                    $statistics['total_deposits'] = $months->sum(function ($month) {
                        return $month->deposits->sum('amount');
                    });
                    break;
                case 'total_expenses':
                    $statistics['total_expenses'] = $months->sum(function ($month) {
                        return $month->purchases->sum('price') + $month->otherCosts->sum('price');
                    });
                    break;
                case 'total_meals':
                    $statistics['total_meals'] = $months->sum(function ($month) {
                        return $month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner');
                    });
                    break;
                case 'user_count':
                    $statistics['avg_user_count'] = $months->avg(function ($month) {
                        return $month->initiatedUser->count();
                    });
                    break;
                case 'avg_meal_cost':
                    $totalMeals = $months->sum(function ($month) {
                        return $month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner');
                    });
                    $totalExpenses = $months->sum(function ($month) {
                        return $month->purchases->sum('price') + $month->otherCosts->sum('price');
                    });
                    $statistics['avg_meal_cost'] = $totalMeals > 0 ? $totalExpenses / $totalMeals : 0;
                    break;
            }
        }

        $statistics['monthly_breakdown'] = $months->map(function ($month) {
            return [
                'month_id' => $month->id,
                'name' => $month->name,
                'deposits' => $month->deposits->sum('amount'),
                'expenses' => $month->purchases->sum('price') + $month->otherCosts->sum('price'),
                'meals' => $month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner'),
                'users' => $month->initiatedUser->count(),
            ];
        });

        return Pipeline::success($statistics, "Statistics retrieved successfully");
    }

    /**
     * Export month data
     */
    public function exportMonth(Month $month, string $format = 'json', bool $includeDetails = true, array $sections = []): Pipeline
    {
        $exportData = [
            'month_info' => [
                'id' => $month->id,
                'name' => $month->name,
                'type' => $month->type,
                'start_date' => $month->start_at,
                'end_date' => $month->end_at,
                'exported_at' => Carbon::now(),
            ]
        ];

        if (in_array('summary', $sections)) {
            $exportData['summary'] = [
                'total_deposits' => $month->deposits->sum('amount'),
                'total_purchases' => $month->purchases->sum('price'),
                'total_other_costs' => $month->otherCosts->sum('price'),
                'total_meals' => $month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner'),
                'user_count' => $month->initiatedUser->count(),
            ];
        }

        if (in_array('meals', $sections)) {
            $exportData['meals'] = $includeDetails
                ? $month->meals->load('messUser.user')
                : $month->meals->pluck('id');
        }

        if (in_array('deposits', $sections)) {
            $exportData['deposits'] = $includeDetails
                ? $month->deposits->load('messUser.user')
                : $month->deposits->pluck('id');
        }

        if (in_array('purchases', $sections)) {
            $exportData['purchases'] = $includeDetails
                ? $month->purchases->load('messUser.user')
                : $month->purchases->pluck('id');
        }

        if (in_array('other_costs', $sections)) {
            $exportData['other_costs'] = $includeDetails
                ? $month->otherCosts->load('messUser.user')
                : $month->otherCosts->pluck('id');
        }

        if (in_array('funds', $sections)) {
            $exportData['funds'] = Fund::where('month_id', $month->id)->get();
        }

        // For now, return JSON format. In a real implementation, you'd handle CSV/Excel conversion
        return Pipeline::success($exportData, "Month data exported successfully");
    }

    /**
     * Get activity timeline
     */
    public function getActivityTimeline(Month $month, Carbon $startDate, Carbon $endDate, array $activityTypes, int $userId = null): Pipeline
    {
        $timeline = collect();

        foreach ($activityTypes as $type) {
            switch ($type) {
                case 'meals':
                    $meals = $month->meals()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->when($userId, function ($q) use ($userId) {
                            $q->where('mess_user_id', $userId);
                        })
                        ->with('messUser.user')
                        ->get();

                    $meals->each(function ($meal) use ($timeline) {
                        $timeline->push([
                            'type' => 'meal',
                            'date' => $meal->date,
                            'user' => $meal->messUser->user->name,
                            'details' => "Breakfast: {$meal->breakfast}, Lunch: {$meal->lunch}, Dinner: {$meal->dinner}",
                            'data' => $meal
                        ]);
                    });
                    break;

                case 'deposits':
                    $deposits = $month->deposits()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->when($userId, function ($q) use ($userId) {
                            $q->where('mess_user_id', $userId);
                        })
                        ->with('messUser.user')
                        ->get();

                    $deposits->each(function ($deposit) use ($timeline) {
                        $timeline->push([
                            'type' => 'deposit',
                            'date' => $deposit->date,
                            'user' => $deposit->messUser->user->name,
                            'details' => "Deposit: ৳{$deposit->amount}",
                            'data' => $deposit
                        ]);
                    });
                    break;

                case 'purchases':
                    $purchases = $month->purchases()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->when($userId, function ($q) use ($userId) {
                            $q->where('mess_user_id', $userId);
                        })
                        ->with('messUser.user')
                        ->get();

                    $purchases->each(function ($purchase) use ($timeline) {
                        $timeline->push([
                            'type' => 'purchase',
                            'date' => $purchase->date,
                            'user' => $purchase->messUser->user->name,
                            'details' => "Purchase: {$purchase->product} - ৳{$purchase->price}",
                            'data' => $purchase
                        ]);
                    });
                    break;

                case 'other_costs':
                    $otherCosts = $month->otherCosts()
                        ->whereBetween('date', [$startDate, $endDate])
                        ->when($userId, function ($q) use ($userId) {
                            $q->where('mess_user_id', $userId);
                        })
                        ->with('messUser.user')
                        ->get();

                    $otherCosts->each(function ($cost) use ($timeline) {
                        $timeline->push([
                            'type' => 'other_cost',
                            'date' => $cost->date,
                            'user' => $cost->messUser->user->name,
                            'details' => "Other Cost: {$cost->product} - ৳{$cost->price}",
                            'data' => $cost
                        ]);
                    });
                    break;
            }
        }

        $sortedTimeline = $timeline->sortBy('date')->values();

        return Pipeline::success([
            'timeline' => $sortedTimeline,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_activities' => $sortedTimeline->count()
        ], "Activity timeline retrieved successfully");
    }

    /**
     * Get budget analysis
     */
    public function getBudgetAnalysis(Month $month, float $budgetAmount = null, array $categoryBudgets = []): Pipeline
    {
        $totalExpenses = $month->purchases->sum('price') + $month->otherCosts->sum('price');

        $analysis = [
            'month_info' => [
                'name' => $month->name,
                'start_date' => $month->start_at,
                'end_date' => $month->end_at,
            ],
            'expenses' => [
                'total_purchases' => $month->purchases->sum('price'),
                'total_other_costs' => $month->otherCosts->sum('price'),
                'total_expenses' => $totalExpenses,
            ],
            'income' => [
                'total_deposits' => $month->deposits->sum('amount'),
            ],
            'balance' => $month->deposits->sum('amount') - $totalExpenses,
        ];

        if ($budgetAmount) {
            $analysis['budget_analysis'] = [
                'budget_amount' => $budgetAmount,
                'actual_expenses' => $totalExpenses,
                'variance' => $budgetAmount - $totalExpenses,
                'percentage_used' => ($totalExpenses / $budgetAmount) * 100,
                'status' => $totalExpenses <= $budgetAmount ? 'within_budget' : 'over_budget',
            ];
        }

        if (!empty($categoryBudgets)) {
            $analysis['category_analysis'] = [];
            // This would require categorizing expenses, which isn't in the current schema
            // but we can provide a framework for it
        }

        return Pipeline::success($analysis, "Budget analysis completed successfully");
    }

    /**
     * Validate month data integrity
     */
    public function validateMonthData(Month $month): Pipeline
    {
        $issues = [];
        $warnings = [];

        // Check for orphaned records
        $orphanedMeals = $month->meals()->doesntHave('messUser')->count();
        if ($orphanedMeals > 0) {
            $issues[] = "Found {$orphanedMeals} meals with invalid mess_user_id";
        }

        $orphanedDeposits = $month->deposits()->doesntHave('messUser')->count();
        if ($orphanedDeposits > 0) {
            $issues[] = "Found {$orphanedDeposits} deposits with invalid mess_user_id";
        }

        // Check for negative amounts
        $negativeDeposits = $month->deposits()->where('amount', '<', 0)->count();
        if ($negativeDeposits > 0) {
            $warnings[] = "Found {$negativeDeposits} deposits with negative amounts";
        }

        // Check for meals without initiated users
        $uninitiated = $month->meals()
            ->whereDoesntHave('messUser.initiatedUser', function ($q) use ($month) {
                $q->where('month_id', $month->id);
            })
            ->count();

        if ($uninitiated > 0) {
            $warnings[] = "Found {$uninitiated} meals from users not initiated for this month";
        }

        // Check date ranges
        $outOfRangeMeals = $month->meals()
            ->where(function ($q) use ($month) {
                $q->where('date', '<', $month->start_at->format('Y-m-d'));
                if ($month->end_at) {
                    $q->orWhere('date', '>', $month->end_at->format('Y-m-d'));
                }
            })
            ->count();

        if ($outOfRangeMeals > 0) {
            $warnings[] = "Found {$outOfRangeMeals} meals outside month date range";
        }

        $validation = [
            'month_id' => $month->id,
            'validation_date' => Carbon::now(),
            'status' => empty($issues) ? 'valid' : 'invalid',
            'issues' => $issues,
            'warnings' => $warnings,
            'summary' => [
                'total_issues' => count($issues),
                'total_warnings' => count($warnings),
            ]
        ];

        return Pipeline::success($validation, "Month data validation completed");
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Month $month, bool $compareWithPrevious = true, bool $includeTrends = true): Pipeline
    {
        $metrics = [
            'month_info' => [
                'id' => $month->id,
                'name' => $month->name,
                'period' => $month->start_at->format('Y-m-d') . ' to ' . ($month->end_at ? $month->end_at->format('Y-m-d') : 'ongoing'),
            ],
            'performance_indicators' => [
                'total_users' => $month->initiatedUser->count(),
                'active_users_percentage' => $month->initiatedUser->where('active', true)->count() / max($month->initiatedUser->count(), 1) * 100,
                'avg_meals_per_user' => $month->initiatedUser->count() > 0 ?
                    ($month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner')) / $month->initiatedUser->count() : 0,
                'avg_deposit_per_user' => $month->initiatedUser->count() > 0 ?
                    $month->deposits->sum('amount') / $month->initiatedUser->count() : 0,
                'cost_per_meal' => $month->meals->count() > 0 ?
                    ($month->purchases->sum('price') + $month->otherCosts->sum('price')) / ($month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner')) : 0,
            ]
        ];

        if ($compareWithPrevious) {
            $previousMonth = $month->mess->months()
                ->where('start_at', '<', $month->start_at)
                ->orderBy('start_at', 'desc')
                ->first();

            if ($previousMonth) {
                $metrics['comparison_with_previous'] = [
                    'previous_month' => $previousMonth->name,
                    'user_change' => $month->initiatedUser->count() - $previousMonth->initiatedUser->count(),
                    'expense_change' => ($month->purchases->sum('price') + $month->otherCosts->sum('price')) -
                                      ($previousMonth->purchases->sum('price') + $previousMonth->otherCosts->sum('price')),
                    'meal_change' => ($month->meals->sum('breakfast') + $month->meals->sum('lunch') + $month->meals->sum('dinner')) -
                                    ($previousMonth->meals->sum('breakfast') + $previousMonth->meals->sum('lunch') + $previousMonth->meals->sum('dinner')),
                ];
            }
        }

        if ($includeTrends) {
            $last6Months = $month->mess->months()
                ->where('start_at', '<=', $month->start_at)
                ->orderBy('start_at', 'desc')
                ->limit(6)
                ->get();

            $metrics['trends'] = [
                'expense_trend' => $last6Months->map(function ($m) {
                    return [
                        'month' => $m->name,
                        'expenses' => $m->purchases->sum('price') + $m->otherCosts->sum('price')
                    ];
                }),
                'user_trend' => $last6Months->map(function ($m) {
                    return [
                        'month' => $m->name,
                        'users' => $m->initiatedUser->count()
                    ];
                })
            ];
        }

        return Pipeline::success($metrics, "Performance metrics retrieved successfully");
    }

    // Helper methods

    private function getDailyBreakdown(Month $month): array
    {
        $startDate = $month->start_at;
        $endDate = $month->end_at ?? Carbon::now();

        $breakdown = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');

            $dayData = [
                'date' => $dateString,
                'meals' => [
                    'breakfast' => $month->meals->where('date', $dateString)->sum('breakfast'),
                    'lunch' => $month->meals->where('date', $dateString)->sum('lunch'),
                    'dinner' => $month->meals->where('date', $dateString)->sum('dinner'),
                ],
                'deposits' => $month->deposits->where('date', $dateString)->sum('amount'),
                'purchases' => $month->purchases->where('date', $dateString)->sum('price'),
                'other_costs' => $month->otherCosts->where('date', $dateString)->sum('price'),
            ];

            $breakdown[] = $dayData;
            $currentDate->addDay();
        }

        return $breakdown;
    }

    private function prepareNextMonthData(Month $currentMonth, string $type, string $name = null): array
    {
        if ($type === MonthType::AUTOMATIC->value) {
            $nextStart = $currentMonth->start_at->copy()->addMonth();
            return [
                'name' => $name ?? $nextStart->format('F Y'),
                'type' => $type,
                'month' => $nextStart->month,
                'year' => $nextStart->year,
                'start_at' => null,
            ];
        } else {
            return [
                'name' => $name ?? 'Next Period',
                'type' => $type,
                'month' => null,
                'year' => null,
                'start_at' => Carbon::now()->format('Y-m-d'),
            ];
        }
    }

    private function getMonthsByPeriod(Mess $mess, string $period): Collection
    {
        $query = $mess->months();

        switch ($period) {
            case 'last_3_months':
                $query->where('start_at', '>=', Carbon::now()->subMonths(3));
                break;
            case 'last_6_months':
                $query->where('start_at', '>=', Carbon::now()->subMonths(6));
                break;
            case 'last_year':
                $query->where('start_at', '>=', Carbon::now()->subYear());
                break;
            case 'all':
            default:
                // No filter, get all months
                break;
        }

        return $query->with(['deposits', 'purchases', 'otherCosts', 'meals', 'initiatedUser'])
                    ->orderBy('start_at', 'desc')
                    ->get();
    }
}

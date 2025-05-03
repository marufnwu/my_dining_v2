<?php

namespace App\Services;

use App\DTOs\CreateMonthDTO;
use App\Enums\MessStatus;
use App\Enums\MonthType;
use App\Helpers\Pipeline;
use App\Models\Mess;
use App\Models\Month;
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
}

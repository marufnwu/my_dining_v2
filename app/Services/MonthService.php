<?php

namespace App\Services;

use App\DTOs\CreateMonthDTO;
use App\Enums\MonthType;
use App\Helpers\Pipeline;
use App\Models\Month;
use Carbon\Carbon;

class MonthService
{
    // Add your service methods here
    public function createMonth(CreateMonthDTO $dto): Pipeline
    {
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
            $data['end_at'] = Carbon::parse($data['start_at'])->endOfMonth();
        }

        unset($data['month'], $data['year']);

        $currentMess = MessService::currentMess();

        $data['mess_id']= $currentMess->id;
        // Create the month
        $month =  Month::create($data);

        return Pipeline::success(data: $month);
    }
}

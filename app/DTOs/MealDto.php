<?php

namespace App\DTOs;

use Carbon\Carbon;

class MealDto
{
    public function __construct(
        public int $monthId,
        public int $messUserId,
        public int $messId,
        public Carbon $date,
        public ?float $breakfast = null,
        public ?float $lunch = null,
        public ?float $dinner = null,
    ) {
    }

    /**
     * Create a MealDto from an array of data.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            monthId: $data['month_id'],
            messUserId: $data['mess_user_id'],
            messId: $data['mess_id'],
            date: Carbon::parse($data['date']),
            breakfast: $data['breakfast'] ?? null,
            lunch: $data['lunch'] ?? null,
            dinner: $data['dinner'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'month_id' => $this->monthId,
            'mess_user_id' => $this->messUserId,
            'mess_id' => $this->messId,
            'date' => $this->date->toDateString(),
            'breakfast' => $this->breakfast,
            'lunch' => $this->lunch,
            'dinner' => $this->dinner,
        ];
    }
}

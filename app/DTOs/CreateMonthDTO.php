<?php

namespace App\DTOs;

class CreateMonthDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly string $type,
        public readonly ?int $month,
        public readonly ?int $year,
        public readonly ?string $start_at,
        public readonly ?bool $force_close_other,
    ) {
    }
}

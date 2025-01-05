<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum SubPlan : string
{
    use EnumToArray;
    case FREE = 'free';
    case BASIC = 'plan-1';
    case PREMIUM = 'plan-2';
    case ENTERPRISE = 'plan-3';
}

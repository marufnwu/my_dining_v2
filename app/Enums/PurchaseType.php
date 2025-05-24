<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PurchaseType : string
{
    use EnumToArray;
    case OTHER = "other";
    case MEAL = "meal";

}

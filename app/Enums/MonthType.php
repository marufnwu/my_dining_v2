<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum MonthType : string
{

    use EnumToArray;
    case MANUAL = "manual";
    case AUTOMATIC = "automatic";

}

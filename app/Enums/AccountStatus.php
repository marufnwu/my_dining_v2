<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum AccountStatus : int
{
    use EnumToArray;
    case ACTIVE = 1;
    case DEACTIVATED = 0;

}

<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ErrorCode : int
{
    use EnumToArray;
    case EMAIL_VERIFICATION_REQUIRED = 1001;
    case AUTHENTICATION_REQUIRED = 1002;
}

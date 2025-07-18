<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ErrorCode : int
{
    use EnumToArray;
    case UNDEFINED = 1000;
    case EMAIL_VERIFICATION_REQUIRED = 1001;
    case AUTHENTICATION_REQUIRED = 1002;
    case NO_MESS_ACCESS = 1003;
    case USER_ALREADY_IN_MESS = 1004;
    case ALREADY_JOINED_MESS = 1005;
    case PERMISSION_DENIED = 1006;
}

<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum MessUserRole : string
{
    use EnumToArray;

    case Admin = "admin";
    case Manager = "manager";
    case Member = "member";
    case GUEST = "guest";
}

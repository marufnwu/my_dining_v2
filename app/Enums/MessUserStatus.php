<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum MessUserStatus : string
{
    use EnumToArray;

    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Blocked = 'blocked';
    case Deleted = 'deleted';
}

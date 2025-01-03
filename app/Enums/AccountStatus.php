<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum AccountStatus : string
{
    use EnumToArray;
    case ACTIVE = "active";
    case DEACTIVATED = "deactivated";
    case DELETED = "deleted";
    case BLOCKED = "blocked";
    case PENDING = "pending";
    case REJECTED = "rejected";
    case SUSPENDED = "suspended";
    

}

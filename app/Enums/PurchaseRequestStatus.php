<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PurchaseRequestStatus : int
{
    use EnumToArray;
    
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;

}

<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum MessPermission : string
{
    use EnumToArray;

    case ALL = "all";
    case USER_MANAGEMENT = "user-management";
    case MEAL_MANAGEMENT = "meal-management";
    case FUND_MANAGEMENT = "fund-management";
    case MESS_SETTING = "mess-setting";
    case MESS_REPORT = "mess-report";
    case MESS_PERMISSION = "mess-permission";
    case EXPENSE_MANAGEMENT = "expense-management";
}

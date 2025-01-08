<?php

namespace App\Enums;

enum Feature : string
{
    case MEMBER_LIMIT = 'Memeber Limit';
    case MESS_REPORT_GENERATE = 'Report Generate';
    case MEAL_ADD_NOTIFICATION = 'Meal Add Notification';
    case BALANCE_ADD_NOTIFICATION = 'Balance Add Notification';
    case PURCHASE_NOTIFICATION = 'Purchase Notification';
    case FUND_ADD = 'Fund Add';
    case ROLE_MANAGEMENT = 'Role Management';
    case PURCHASE_REQUEST = 'Purchase Request';

}

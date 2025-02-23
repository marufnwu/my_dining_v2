<?php

namespace App\Constants;

class MessPermission
{
    // Existing permissions
    const USER_MANAGEMENT = 'user_management';
    const MEAL_MANAGEMENT = 'meal_management';
    const PURCHASE_MANAGEMENT = 'purchase_management';
    const DEPOSIT_MANAGEMENT = 'deposit-management';
    const REPORT_MANAGEMENT = 'report-management';
    const NOTICE_MANAGEMENT = 'notice-management';
    const PERMISSION_MANAGEMENT = 'notice-management';

    // New permissions
    const USER_ADD = 'user_add';
    const USER_REMOVE = 'user_remove';
    const USER_EDIT = 'user_edit';
    const MEAL_ADD = 'meal_add';
    const MEAL_EDIT = 'meal_edit';
    const MEAL_DELETE = 'meal_delete';
    const PURCHASE_ADD = 'purchase_add';
    const PURCHASE_EDIT = 'purchase_edit';
    const PURCHASE_DELETE = 'purchase_delete';
    const DEPOSIT_ADD = 'deposit_add';
    const DEPOSIT_REMOVE = 'deposit_remove';
    const DEPOSIT_DELETE = 'deposit_delete';
    const GENERATE_REPORT = 'generate_report';
    const SEND_NOTIFICATION = 'send_notification';
    const NOTICE_ADD = 'notice_add';
}

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
    const PERMISSION_MANAGEMENT = 'permission-management';



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


    // Purchase Request Permissions
    const PURCHASE_REQUEST_CREATE = 'purchase_request_create';
    const PURCHASE_REQUEST_UPDATE = 'purchase_request_update';
    const PURCHASE_REQUEST_DELETE = 'purchase_request_delete';
    const PURCHASE_REQUEST_APPROVE = 'purchase_request_approve';
    const PURCHASE_REQUEST_REJECT = 'purchase_request_reject';
    const PURCHASE_REQUEST_VIEW = 'purchase_request_view';
    const PURCHASE_REQUEST_MANAGEMENT = 'purchase_management';

    // Mess Join Request Permissions
    const MESS_JOIN_REQUEST_MANAGE = 'mess_join_request_manage';
    const MESS_CLOSE = 'mess_close';
    const MESS_SETTINGS = 'mess_settings';

    // Meal Request Permissions
    const MEAL_REQUEST_CREATE = 'meal_request_create';
    const MEAL_REQUEST_UPDATE = 'meal_request_update';
    const MEAL_REQUEST_DELETE = 'meal_request_delete';
    const MEAL_REQUEST_APPROVE = 'meal_request_approve';
    const MEAL_REQUEST_REJECT = 'meal_request_reject';
    const MEAL_REQUEST_VIEW = 'meal_request_view';
    const MEAL_REQUEST_MANAGEMENT = 'meal_request_management';

}

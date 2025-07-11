<?php

namespace App\Enums;

enum NotificationTemplate: string
{
    case MEAL_REQUEST_PENDING = 'meal_request_pending';
    case MEAL_REQUEST_APPROVED = 'meal_request_approved';
    case MEAL_REQUEST_REJECTED = 'meal_request_rejected';
    case NEW_DEPOSIT = 'new_deposit';
    case DEPOSIT_APPROVED = 'deposit_approved';
    case DEPOSIT_REJECTED = 'deposit_rejected';
    case LOW_BALANCE_WARNING = 'low_balance_warning';
    case PURCHASE_RECORDED = 'purchase_recorded';
    case MONTHLY_BILL_GENERATED = 'monthly_bill_generated';
    case MESS_CLOSED_TEMPORARILY = 'mess_closed_temporarily';
    case MESS_REOPENED = 'mess_reopened';
    case WELCOME_NEW_MEMBER = 'welcome_new_member';
    case MEMBER_LEFT = 'member_left';
    case CUSTOM_ANNOUNCEMENT = 'custom_announcement';
    case SYSTEM_MAINTENANCE = 'system_maintenance';
    case MEAL_SCHEDULE_UPDATED = 'meal_schedule_updated';
    case PRICE_UPDATE = 'price_update';
    case PAYMENT_REMINDER = 'payment_reminder';

    public function getTemplate(): array
    {
        return match($this) {
            self::MEAL_REQUEST_PENDING => [
                'title' => 'New Meal Request Pending',
                'body' => '{requester_name} has requested meals for {meal_date}. Please review and approve.',
                'category' => NotificationCategory::MEAL,
                'priority' => NotificationPriority::NORMAL,
                'is_actionable' => true,
                'action_data' => [
                    'approve' => 'Approve',
                    'reject' => 'Reject',
                    'view' => 'View Details',
                    'deep_link' => '/meal-requests/{request_id}',
                ],
            ],

            self::MEAL_REQUEST_APPROVED => [
                'title' => 'Meal Request Approved',
                'body' => 'Your meal request for {meal_date} has been approved. Total cost: ৳{total_amount}',
                'category' => NotificationCategory::MEAL,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::MEAL_REQUEST_REJECTED => [
                'title' => 'Meal Request Rejected',
                'body' => 'Your meal request for {meal_date} has been rejected. Reason: {rejection_reason}',
                'category' => NotificationCategory::MEAL,
                'priority' => NotificationPriority::HIGH,
            ],

            self::NEW_DEPOSIT => [
                'title' => 'New Deposit Received',
                'body' => '{depositor_name} has made a deposit of ৳{amount}. Please verify and approve.',
                'category' => NotificationCategory::DEPOSIT,
                'priority' => NotificationPriority::NORMAL,
                'is_actionable' => true,
                'action_data' => [
                    'approve' => 'Approve',
                    'reject' => 'Reject',
                    'view' => 'View Details',
                    'deep_link' => '/deposits/{deposit_id}',
                ],
            ],

            self::DEPOSIT_APPROVED => [
                'title' => 'Deposit Approved',
                'body' => 'Your deposit of ৳{amount} has been approved. New balance: ৳{new_balance}',
                'category' => NotificationCategory::DEPOSIT,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::DEPOSIT_REJECTED => [
                'title' => 'Deposit Rejected',
                'body' => 'Your deposit of ৳{amount} has been rejected. Reason: {rejection_reason}',
                'category' => NotificationCategory::DEPOSIT,
                'priority' => NotificationPriority::HIGH,
            ],

            self::LOW_BALANCE_WARNING => [
                'title' => 'Low Balance Warning',
                'body' => 'Your balance is low (৳{current_balance}). Please add funds to continue using mess services.',
                'category' => NotificationCategory::ALERT,
                'priority' => NotificationPriority::HIGH,
                'is_actionable' => true,
                'action_data' => [
                    'add_deposit' => 'Add Deposit',
                    'view_balance' => 'View Balance',
                    'deep_link' => '/deposits/create',
                ],
            ],

            self::PURCHASE_RECORDED => [
                'title' => 'Purchase Recorded',
                'body' => 'A purchase of ৳{amount} has been recorded for {item_description}. Remaining balance: ৳{remaining_balance}',
                'category' => NotificationCategory::PURCHASE,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::MONTHLY_BILL_GENERATED => [
                'title' => 'Monthly Bill Generated',
                'body' => 'Your monthly bill for {month_year} is ready. Total amount: ৳{total_amount}',
                'category' => NotificationCategory::FINANCIAL,
                'priority' => NotificationPriority::NORMAL,
                'is_actionable' => true,
                'action_data' => [
                    'view_bill' => 'View Bill',
                    'download' => 'Download PDF',
                    'deep_link' => '/bills/{bill_id}',
                ],
            ],

            self::MESS_CLOSED_TEMPORARILY => [
                'title' => 'Mess Temporarily Closed',
                'body' => 'The mess will be closed from {start_date} to {end_date} due to {reason}.',
                'category' => NotificationCategory::MESS_MANAGEMENT,
                'priority' => NotificationPriority::HIGH,
            ],

            self::MESS_REOPENED => [
                'title' => 'Mess Reopened',
                'body' => 'The mess has reopened and is now serving meals. Welcome back!',
                'category' => NotificationCategory::MESS_MANAGEMENT,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::WELCOME_NEW_MEMBER => [
                'title' => 'Welcome to {mess_name}!',
                'body' => 'Welcome {member_name}! You have successfully joined our mess. Start by adding a deposit to begin ordering meals.',
                'category' => NotificationCategory::USER_ACTIVITY,
                'priority' => NotificationPriority::NORMAL,
                'is_actionable' => true,
                'action_data' => [
                    'add_deposit' => 'Add Deposit',
                    'view_menu' => 'View Menu',
                    'deep_link' => '/deposits/create',
                ],
            ],

            self::MEMBER_LEFT => [
                'title' => 'Member Left Mess',
                'body' => '{member_name} has left the mess. Final balance: ৳{final_balance}',
                'category' => NotificationCategory::USER_ACTIVITY,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::CUSTOM_ANNOUNCEMENT => [
                'title' => '{announcement_title}',
                'body' => '{announcement_body}',
                'category' => NotificationCategory::ANNOUNCEMENT,
                'priority' => NotificationPriority::NORMAL,
            ],

            self::SYSTEM_MAINTENANCE => [
                'title' => 'System Maintenance Scheduled',
                'body' => 'The system will be under maintenance from {start_time} to {end_time}. Some features may be unavailable.',
                'category' => NotificationCategory::SYSTEM,
                'priority' => NotificationPriority::HIGH,
                'is_dismissible' => false,
            ],

            self::MEAL_SCHEDULE_UPDATED => [
                'title' => 'Meal Schedule Updated',
                'body' => 'The meal schedule for {date_range} has been updated. Please check the new timings.',
                'category' => NotificationCategory::MEAL,
                'priority' => NotificationPriority::NORMAL,
                'is_actionable' => true,
                'action_data' => [
                    'view_schedule' => 'View Schedule',
                    'deep_link' => '/meal-schedule',
                ],
            ],

            self::PRICE_UPDATE => [
                'title' => 'Meal Prices Updated',
                'body' => 'Meal prices have been updated effective from {effective_date}. New prices: Breakfast ৳{breakfast_price}, Lunch ৳{lunch_price}, Dinner ৳{dinner_price}',
                'category' => NotificationCategory::MESS_MANAGEMENT,
                'priority' => NotificationPriority::HIGH,
            ],

            self::PAYMENT_REMINDER => [
                'title' => 'Payment Reminder',
                'body' => 'Your payment of ৳{amount} is due on {due_date}. Please make the payment to avoid service interruption.',
                'category' => NotificationCategory::REMINDER,
                'priority' => NotificationPriority::HIGH,
                'is_actionable' => true,
                'action_data' => [
                    'pay_now' => 'Pay Now',
                    'view_details' => 'View Details',
                    'deep_link' => '/payments/{payment_id}',
                ],
            ],
        ];
    }

    public function fillTemplate(array $params): array
    {
        $template = $this->getTemplate();

        $template['title'] = $this->replacePlaceholders($template['title'], $params);
        $template['body'] = $this->replacePlaceholders($template['body'], $params);

        if (isset($template['action_data']['deep_link'])) {
            $template['action_data']['deep_link'] = $this->replacePlaceholders($template['action_data']['deep_link'], $params);
        }

        return $template;
    }

    private function replacePlaceholders(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }

    public function getRequiredParams(): array
    {
        $template = $this->getTemplate();
        $text = $template['title'] . ' ' . $template['body'];

        if (isset($template['action_data']['deep_link'])) {
            $text .= ' ' . $template['action_data']['deep_link'];
        }

        preg_match_all('/\{([^}]+)\}/', $text, $matches);
        return array_unique($matches[1]);
    }
}

<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case MEAL = 'meal';
    case DEPOSIT = 'deposit';
    case PURCHASE = 'purchase';
    case MESS_MANAGEMENT = 'mess_management';
    case USER_ACTIVITY = 'user_activity';
    case SYSTEM = 'system';
    case FINANCIAL = 'financial';
    case REMINDER = 'reminder';
    case ALERT = 'alert';
    case ANNOUNCEMENT = 'announcement';

    public function getIcon(): string
    {
        return match($this) {
            self::MEAL => '🍽️',
            self::DEPOSIT => '💰',
            self::PURCHASE => '🛒',
            self::MESS_MANAGEMENT => '🏠',
            self::USER_ACTIVITY => '👥',
            self::SYSTEM => '⚙️',
            self::FINANCIAL => '📊',
            self::REMINDER => '⏰',
            self::ALERT => '🚨',
            self::ANNOUNCEMENT => '📢',
        };
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::MEAL => 'Meal Management',
            self::DEPOSIT => 'Deposits',
            self::PURCHASE => 'Purchases',
            self::MESS_MANAGEMENT => 'Mess Management',
            self::USER_ACTIVITY => 'User Activities',
            self::SYSTEM => 'System',
            self::FINANCIAL => 'Financial',
            self::REMINDER => 'Reminders',
            self::ALERT => 'Alerts',
            self::ANNOUNCEMENT => 'Announcements',
        };
    }
}

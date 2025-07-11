<?php

namespace App\Enums;

enum NotificationTemplate: string
{
    case MEAL_REQUEST_APPROVED = 'meal_request_approved';
    case MEAL_REQUEST_REJECTED = 'meal_request_rejected';
    case NEW_DEPOSIT = 'new_deposit';
    case DEPOSIT_UPDATED = 'deposit_updated';
    case JOIN_REQUEST_RECEIVED = 'join_request_received';
    case JOIN_REQUEST_ACCEPTED = 'join_request_accepted';
    case JOIN_REQUEST_REJECTED = 'join_request_rejected';
    case PURCHASE_REQUEST_APPROVED = 'purchase_request_approved';
    case PURCHASE_REQUEST_REJECTED = 'purchase_request_rejected';
    case MESS_CLOSED = 'mess_closed';
    case BALANCE_LOW = 'balance_low';
    case MEAL_COUNT_UPDATED = 'meal_count_updated';
    case NEW_MONTH_STARTED = 'new_month_started';

    public function getTemplate(): array
    {
        return match($this) {
            self::MEAL_REQUEST_APPROVED => [
                'title' => 'Meal Request Approved',
                'body' => '{userName}\'s meal request for {date} has been approved',
            ],
            self::MEAL_REQUEST_REJECTED => [
                'title' => 'Meal Request Rejected',
                'body' => '{userName}\'s meal request for {date} has been rejected',
            ],
            self::NEW_DEPOSIT => [
                'title' => 'New Deposit Added',
                'body' => '{userName} added a deposit of ৳{amount}',
            ],
            self::DEPOSIT_UPDATED => [
                'title' => 'Deposit Updated',
                'body' => 'Your deposit of ৳{amount} has been updated',
            ],
            self::JOIN_REQUEST_RECEIVED => [
                'title' => 'New Join Request',
                'body' => '{userName} has requested to join {messName}',
            ],
            self::JOIN_REQUEST_ACCEPTED => [
                'title' => 'Join Request Accepted',
                'body' => 'Your request to join {messName} has been accepted',
            ],
            self::JOIN_REQUEST_REJECTED => [
                'title' => 'Join Request Rejected',
                'body' => 'Your request to join {messName} has been rejected',
            ],
            self::PURCHASE_REQUEST_APPROVED => [
                'title' => 'Purchase Request Approved',
                'body' => 'Your purchase request for ৳{amount} has been approved',
            ],
            self::PURCHASE_REQUEST_REJECTED => [
                'title' => 'Purchase Request Rejected',
                'body' => 'Your purchase request for ৳{amount} has been rejected',
            ],
            self::MESS_CLOSED => [
                'title' => 'Mess Closed',
                'body' => '{messName} has been closed',
            ],
            self::BALANCE_LOW => [
                'title' => 'Low Balance Alert',
                'body' => 'Your mess balance is low. Current balance: ৳{balance}',
            ],
            self::MEAL_COUNT_UPDATED => [
                'title' => 'Meal Count Updated',
                'body' => 'Your meal count for {date} has been updated to {count}',
            ],
            self::NEW_MONTH_STARTED => [
                'title' => 'New Month Started',
                'body' => 'A new month ({monthName}) has started in {messName}',
            ],
        };
    }
}

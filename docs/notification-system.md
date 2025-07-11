# Notification System Documentation

## Overview
The notification system in My Dining v2 provides real-time notifications using Firebase Cloud Messaging (FCM) for push notifications and a database-backed notification history. The system supports both user-specific and broadcast notifications across the mess management system.

## Features
- Real-time push notifications via Firebase Cloud Messaging (FCM)
- Persistent notification history in the database
- Support for both targeted and broadcast notifications
- Read/unread status tracking
- Notification categorization by type
- Rich notification data support

## Setup Requirements

### Environment Variables
Add the following to your `.env` file:
```
FIREBASE_SERVER_KEY=your_server_key
FIREBASE_API_KEY=your_api_key
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_MESSAGING_SENDER_ID=your_messaging_sender_id
FIREBASE_APP_ID=your_app_id
```

### Client-Side FCM Integration
1. Initialize Firebase in your client application
2. Request notification permissions
3. Get the FCM token and send it to the backend
4. Update the token when it refreshes

## API Endpoints

### List Notifications
```http
GET /api/notifications
```
Query Parameters:
- `type` (optional): Filter by notification type
- `unread_only` (optional): Show only unread notifications
- `per_page` (optional): Number of notifications per page (default: 15)

### Mark Notification as Read
```http
POST /api/notifications/{notification}/read
```

### Mark All Notifications as Read
```http
POST /api/notifications/read-all
```

### Update FCM Token
```http
POST /api/notifications/fcm-token
```
Request body:
```json
{
    "token": "your_fcm_token"
}
```

## Notification Types
The system includes several predefined notification types:

### Meal Related
- `meal_request_approved`: When a meal request is approved
- `meal_request_rejected`: When a meal request is rejected
- `new_meal_request`: When a new meal request is created
- `meal_request_updated`: When a meal request is updated
- `meal_request_cancelled`: When a meal request is cancelled

### Deposit Related
- `deposit_added`: When a new deposit is added
- `deposit_updated`: When a deposit is updated
- `deposit_deleted`: When a deposit is deleted
- `deposit_added_admin`: Broadcast to admins when any deposit is added

### Purchase Related
- `new_purchase_request`: When a new purchase request is created
- `purchase_request_updated`: When a purchase request is updated
- `purchase_request_approved`: When a purchase request is approved
- `purchase_request_rejected`: When a purchase request is rejected
- `purchase_request_deleted`: When a purchase request is deleted

### Mess Management
- `join_request`: When a user requests to join the mess
- `join_request_accepted`: When a join request is accepted
- `join_request_rejected`: When a join request is rejected
- `member_joined`: When a new member joins the mess
- `member_left`: When a member leaves the mess
- `mess_closed`: When a mess is closed

## Using the NotificationService

### Sending a Notification
```php
// Inject the NotificationService
public function __construct(protected NotificationService $notificationService) {}

// Send a user-specific notification
$this->notificationService->sendNotification([
    'user_id' => $userId,
    'title' => 'Notification Title',
    'body' => 'Notification message',
    'type' => 'notification_type',
    'extra_data' => [
        'key' => 'value'
    ]
]);

// Send a broadcast notification
$this->notificationService->sendNotification([
    'title' => 'Broadcast Message',
    'body' => 'Message for all mess members',
    'type' => 'broadcast_type',
    'is_broadcast' => true,
    'extra_data' => [
        'key' => 'value'
    ]
]);
```

### Notification Structure
Each notification includes:
- `title`: The notification title
- `body`: The notification message
- `type`: The notification type (for categorization)
- `data`: Additional JSON data specific to the notification type
- `is_broadcast`: Whether the notification is sent to all mess members
- `read_at`: Timestamp when the notification was read
- `created_at`: Timestamp when the notification was created

## Best Practices

1. **Notification Types**
   - Use consistent notification types across the application
   - Document new notification types when adding them
   - Use descriptive type names that indicate the notification's purpose

2. **Extra Data**
   - Include relevant IDs and references in extra_data
   - Keep extra data concise and relevant
   - Include only data needed for handling the notification

3. **Message Content**
   - Keep titles short and descriptive
   - Provide clear and actionable message bodies
   - Include relevant user names and amounts where applicable

4. **FCM Token Management**
   - Update FCM tokens whenever they refresh
   - Handle token invalidation gracefully
   - Remove invalid tokens when push notifications fail

## Error Handling

The notification system handles several types of errors:
- Invalid FCM tokens
- Network failures
- Database errors
- Invalid notification data

Errors are logged and can be monitored through Laravel's logging system.

## Examples

### Meal Request Notification
```php
$this->notificationService->sendNotification([
    'user_id' => $mealRequest->messUser->user_id,
    'title' => 'Meal Request Approved',
    'body' => "Your meal request for {$mealRequest->date} has been approved",
    'type' => 'meal_request_approved',
    'extra_data' => [
        'meal_request_id' => $mealRequest->id,
        'date' => $mealRequest->date
    ]
]);
```

### Broadcast Deposit Notification
```php
$this->notificationService->sendNotification([
    'title' => 'New Deposit',
    'body' => "{$deposit->messUser->user->name} added a deposit of ৳{$deposit->amount}",
    'type' => 'deposit_added_admin',
    'is_broadcast' => true,
    'extra_data' => [
        'deposit_id' => $deposit->id,
        'amount' => $deposit->amount,
        'user_id' => $deposit->messUser->user_id
    ]
]);
```

## Testing

The notification system can be tested using Laravel's testing framework:

```php
public function test_notification_is_sent()
{
    // Arrange
    $user = User::factory()->create(['fcm_token' => 'test_token']);
    
    // Act
    $this->notificationService->sendNotification([
        'user_id' => $user->id,
        'title' => 'Test Notification',
        'body' => 'Test message',
        'type' => 'test'
    ]);
    
    // Assert
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'title' => 'Test Notification',
        'type' => 'test'
    ]);
}
```

## Troubleshooting

### Common Issues

1. **Notifications not being received**
   - Check FCM token validity
   - Verify Firebase configuration
   - Ensure device has granted notification permissions

2. **Database notifications not appearing**
   - Check database migrations are up to date
   - Verify user IDs and mess IDs are correct
   - Check for any database transaction rollbacks

3. **FCM errors**
   - Verify Firebase credentials
   - Check network connectivity
   - Validate FCM token format

### Debugging

Enable debug logging for notifications by adding this to your `.env`:
```
LOG_CHANNEL=daily
LOG_LEVEL=debug
```

Monitor the Laravel logs in `storage/logs` for detailed error messages and debugging information.

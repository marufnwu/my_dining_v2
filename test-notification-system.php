#!/usr/bin/env php
<?php

/**
 * Notification System Test Script
 *
 * This script tests the notification system in the My Dining v2 application.
 * It verifies that notifications can be sent, received, and managed properly.
 */

// Define base URL and colors for console output
$baseUrl = 'http://md.local/api'; // Update with your actual server URL
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$CYAN = "\033[36m";
$RESET = "\033[0m";

// Test results tracking
$results = [];
$testCount = 0;
$passedCount = 0;
$failedCount = 0;

echo "{$CYAN}🔔 NOTIFICATION SYSTEM TEST{$RESET}\n";
echo "=====================================\n";
echo "Testing notification endpoints at {$baseUrl}\n\n";

// Helper function to make HTTP requests
function makeRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();

    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    $headers = array_merge($defaultHeaders, $headers);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    $data = json_decode($response, true);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'data' => $data
    ];
}

// Helper function to log test results
function logTest($name, $result, $notes = '') {
    global $testCount, $passedCount, $failedCount, $results, $GREEN, $RED, $RESET;

    $testCount++;
    $success = false;

    // Consider test passed if HTTP code is 2xx or if explicitly marked success
    if (
        ($result['http_code'] >= 200 && $result['http_code'] < 300) ||
        (isset($result['data']['status']) && $result['data']['status'] === 'success') ||
        (isset($result['data']['error']) && $result['data']['error'] === false)
    ) {
        $passedCount++;
        $success = true;
        echo "  {$GREEN}✅ PASS: {$name}{$RESET}\n";
    } else {
        $failedCount++;
        echo "  {$RED}❌ FAIL: {$name}{$RESET}\n";
    }

    echo "     HTTP Code: {$result['http_code']}\n";

    if (!empty($notes)) {
        echo "     Notes: {$notes}\n";
    }

    // Only show detailed response for failed tests to keep output clean
    if (!$success) {
        echo "     Response: " . substr($result['response'], 0, 150) . (strlen($result['response']) > 150 ? "..." : "") . "\n";
    }

    $results[] = [
        'name' => $name,
        'success' => $success,
        'http_code' => $result['http_code'],
        'notes' => $notes
    ];

    echo "\n";
}

// Login and get auth token
echo "🔑 Authenticating...\n";
$loginData = [
    'email' => 'admin@example.com',
    'password' => 'password'
];

// Change these to match a valid admin user in your system
$loginData = [
    'email' => 'maruf@email.com',
    'password' => '11111111'
];

$loginResult = makeRequest('POST', "{$baseUrl}/auth/login", $loginData);

$token = null;
if ($loginResult['http_code'] === 200 && isset($loginResult['data']['data']['token'])) {
    $token = $loginResult['data']['data']['token'];
    echo "{$GREEN}✅ Authentication successful!{$RESET}\n\n";
} elseif ($loginResult['http_code'] === 200 && isset($loginResult['data']['token'])) {
    // Alternate response format
    $token = $loginResult['data']['token'];
    echo "{$GREEN}✅ Authentication successful!{$RESET}\n\n";
} else {
    echo "{$RED}❌ Authentication failed! Cannot continue tests.{$RESET}\n";
    echo "Response: " . $loginResult['response'] . "\n";
    exit(1);
}

// Create auth headers with token
$authHeaders = [
    'Authorization: Bearer ' . $token
];

// BASIC TESTS

echo "🧪 RUNNING BASIC NOTIFICATION TESTS\n";
echo "-----------------------------------\n";

// Test 1: Get user notifications
echo "1. Get User Notifications...\n";
$result = makeRequest('GET', "{$baseUrl}/notifications", null, $authHeaders);
logTest('Get User Notifications', $result);

// Test 2: Get notification stats
echo "2. Get Notification Stats...\n";
$result = makeRequest('GET', "{$baseUrl}/notifications/stats", null, $authHeaders);
logTest('Get Notification Stats', $result);

// Test 3: Get notification templates
echo "3. Get Notification Templates...\n";
$result = makeRequest('GET', "{$baseUrl}/notifications/templates", null, $authHeaders);
logTest('Get Notification Templates', $result);

// Store templates for later use
$templates = $result['data']['data'] ?? [];
$templateIds = [];
if (!empty($templates)) {
    foreach ($templates as $template) {
        $templateIds[] = $template['key'];
    }
}

// Test 4: Get notification categories
echo "4. Get Notification Categories...\n";
$result = makeRequest('GET', "{$baseUrl}/notifications/categories", null, $authHeaders);
logTest('Get Notification Categories', $result);

// Store categories for later use
$categories = [];
if (isset($result['data']['data'])) {
    foreach ($result['data']['data'] as $category) {
        $categories[$category['key']] = $category['name'];
    }
}

// Test 5: Get notification priorities
echo "5. Get Notification Priorities...\n";
$result = makeRequest('GET', "{$baseUrl}/notifications/priorities", null, $authHeaders);
logTest('Get Notification Priorities', $result);

// Store priorities for later use
$priorities = [];
if (isset($result['data']['data'])) {
    foreach ($result['data']['data'] as $priority) {
        $priorities[$priority['key']] = $priority['name'];
    }
}

// Test 6: Update FCM Token
echo "6. Update FCM Token...\n";
$fcmData = [
    'token' => 'test_fcm_token_' . time()
];
$result = makeRequest('POST', "{$baseUrl}/notifications/fcm-token", $fcmData, $authHeaders);
logTest('Update FCM Token', $result);

// Test 7: Mark all as read
echo "7. Mark All As Read...\n";
$result = makeRequest('POST', "{$baseUrl}/notifications/read-all", null, $authHeaders);
logTest('Mark All As Read', $result);

// ADVANCED TESTS (ADMIN/MANAGER ONLY)

echo "\n🧪 RUNNING ADMIN NOTIFICATION TESTS\n";
echo "-----------------------------------\n";

// Get a list of user IDs to send notifications to
echo "8. Get users to send notifications to...\n";
$result = makeRequest('GET', "{$baseUrl}/member/list", null, $authHeaders);

$recipientIds = [];
if (isset($result['data']['data'])) {
    foreach ($result['data']['data'] as $member) {
        if (isset($member['user']['id'])) {
            $recipientIds[] = $member['user']['id'];
        } else if (isset($member['id'])) {
            $recipientIds[] = $member['id'];
        }
    }
}

if (empty($recipientIds)) {
    // If we couldn't get IDs, use current user's ID
    $recipientIds = [1]; // Assuming user ID 1 exists
    echo "{$YELLOW}⚠️ Could not get user IDs, using default ID: 1{$RESET}\n\n";
} else {
    echo "{$GREEN}✅ Found " . count($recipientIds) . " user(s) to send notifications to{$RESET}\n\n";
}

// Test 9: Send custom notification
echo "9. Send Custom Notification...\n";
$customNotificationData = [
    'recipients' => array_slice($recipientIds, 0, 1),
    'title' => 'Test Notification',
    'body' => 'This is a test notification from the API test script',
    'type' => 'test_notification',
    'category' => isset($categories) && !empty($categories) ? array_key_first($categories) : 'system',
    'priority' => isset($priorities) && !empty($priorities) ? array_key_first($priorities) : 'normal',
    'data' => ['test' => true, 'timestamp' => time()],
    'is_actionable' => false
];
$result = makeRequest('POST', "{$baseUrl}/notifications/send/custom", $customNotificationData, $authHeaders);
logTest('Send Custom Notification', $result);

// Store the notification ID if available
$notificationId = null;
if (isset($result['data']['data']) && is_array($result['data']['data']) && count($result['data']['data']) > 0) {
    $notificationId = $result['data']['data'][0]['id'] ?? null;
}

// Test 10: Send template notification (if we have templates)
echo "10. Send Template Notification...\n";
if (!empty($templateIds)) {
    $templateNotificationData = [
        'template' => $templateIds[0],
        'recipients' => array_slice($recipientIds, 0, 1),
        'params' => [
            'requester_name' => 'Test User',
            'meal_date' => date('Y-m-d'),
            'request_id' => '12345'
            // Add other required parameters based on template needs
        ]
    ];
    $result = makeRequest('POST', "{$baseUrl}/notifications/send/template", $templateNotificationData, $authHeaders);
    logTest('Send Template Notification', $result, 'Using template: ' . $templateIds[0]);
} else {
    echo "{$YELLOW}⚠️ No templates found, skipping template notification test{$RESET}\n\n";
}

// Test 11: Send to all members
echo "11. Send Notification To All Members...\n";
$allMembersData = [
    'title' => 'Announcement to All Members',
    'body' => 'This is a test announcement to all mess members.',
    'type' => 'test_broadcast',
    'category' => 'announcement',
    'priority' => 'normal'
];
$result = makeRequest('POST', "{$baseUrl}/notifications/send/all-members", $allMembersData, $authHeaders);
logTest('Send to All Members', $result);

// Test 12: Send notification to admins
echo "12. Send Notification To Admins...\n";
$adminData = [
    'title' => 'Admin Notification',
    'body' => 'This is a test notification for admins only.',
    'type' => 'admin_test',
    'category' => 'system',
    'priority' => 'high'
];
$result = makeRequest('POST', "{$baseUrl}/notifications/send/admins", $adminData, $authHeaders);
logTest('Send to Admins', $result);

// Test 13: Send to specific roles
echo "13. Send Notification To Specific Roles...\n";
$rolesData = [
    'roles' => ['admin', 'manager'],
    'title' => 'Role-specific Notification',
    'body' => 'This is a test notification for specific roles.',
    'category' => 'mess_management',
    'priority' => 'normal'
];
$result = makeRequest('POST', "{$baseUrl}/notifications/send/roles", $rolesData, $authHeaders);
logTest('Send to Roles', $result);

// Test 14: Send actionable notification
echo "14. Send Actionable Notification...\n";
$actionableData = [
    'recipients' => array_slice($recipientIds, 0, 1),
    'title' => 'Actionable Notification',
    'body' => 'This notification requires action from you.',
    'actions' => [
        ['key' => 'approve', 'label' => 'Approve'],
        ['key' => 'reject', 'label' => 'Reject'],
        'deep_link' => '/test-action/12345'
    ]
];
$result = makeRequest('POST', "{$baseUrl}/notifications/send/actionable", $actionableData, $authHeaders);
logTest('Send Actionable Notification', $result);

// Test 15: Schedule a notification for the future
echo "15. Schedule Notification...\n";
$scheduleData = [
    'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 minute')),
    'recipients' => array_slice($recipientIds, 0, 1),
    'title' => 'Scheduled Notification',
    'body' => 'This notification was scheduled to be delivered 1 minute after the test.'
];
$result = makeRequest('POST', "{$baseUrl}/notifications/schedule", $scheduleData, $authHeaders);
logTest('Schedule Notification', $result);

// Test 16: Process scheduled notifications
echo "16. Process Scheduled Notifications...\n";
$result = makeRequest('POST', "{$baseUrl}/notifications/process-scheduled", null, $authHeaders);
logTest('Process Scheduled Notifications', $result);

// Test 17: Mark a specific notification as read (if we have one)
echo "17. Mark Specific Notification As Read...\n";
if ($notificationId) {
    $result = makeRequest('POST', "{$baseUrl}/notifications/{$notificationId}/read", null, $authHeaders);
    logTest('Mark Notification As Read', $result, "Notification ID: {$notificationId}");
} else {
    echo "{$YELLOW}⚠️ No notification ID available, skipping mark as read test{$RESET}\n\n";
}

// Test 18: Cleanup expired notifications
echo "18. Cleanup Expired Notifications...\n";
$result = makeRequest('DELETE', "{$baseUrl}/notifications/cleanup-expired", null, $authHeaders);
logTest('Cleanup Expired Notifications', $result);

// Test 19: Get notifications with filters
echo "19. Get Filtered Notifications...\n";
$filters = '?unread_only=true&per_page=5';
if (!empty($categories)) {
    $filters .= '&category=' . array_key_first($categories);
}
$result = makeRequest('GET', "{$baseUrl}/notifications{$filters}", null, $authHeaders);
logTest('Get Filtered Notifications', $result, 'Using filters: ' . $filters);

// SUMMARY
echo "\n📊 TEST SUMMARY\n";
echo "===============\n";
echo "Total Tests: {$testCount}\n";
echo "{$GREEN}Passed: {$passedCount}{$RESET}\n";
echo "{$RED}Failed: {$failedCount}{$RESET}\n";
echo "Success Rate: " . round(($passedCount / $testCount) * 100, 1) . "%\n\n";

if ($failedCount > 0) {
    echo "{$RED}FAILED TESTS:{$RESET}\n";
    foreach ($results as $test) {
        if (!$test['success']) {
            echo "❌ {$test['name']} (HTTP Code: {$test['http_code']})\n";
        }
    }
    echo "\n";
}

echo "{$CYAN}🔔 NOTIFICATION SYSTEM TESTING COMPLETED{$RESET}\n";
echo "=========================================\n";

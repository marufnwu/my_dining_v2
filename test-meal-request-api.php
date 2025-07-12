#!/usr/bin/env php
<?php

/**
 * Comprehensive Meal Request API Testing Script
 * Tests all endpoints and validates response formats
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000/api';
$testResults = [];

// Test user credentials (adjust as needed)
$userCredentials = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

$adminCredentials = [
    'email' => 'admin@example.com',
    'password' => 'admin123'
];

// Helper functions
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
        case 'GET':
        default:
            // GET is default
            break;
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

function logTest($testName, $result) {
    global $testResults;

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TEST: $testName\n";
    echo str_repeat("=", 80) . "\n";

    echo "HTTP Code: " . $result['http_code'] . "\n";
    echo "Response: " . $result['response'] . "\n";

    if ($result['error']) {
        echo "Error: " . $result['error'] . "\n";
    }

    $testResults[] = [
        'test' => $testName,
        'http_code' => $result['http_code'],
        'success' => $result['http_code'] >= 200 && $result['http_code'] < 300,
        'response' => $result['data']
    ];
}

function authenticate($credentials) {
    global $baseUrl;

    echo "\n� Authenticating user: " . $credentials['email'] . "\n";

    $result = makeRequest('POST', "$baseUrl/login", $credentials);

    if ($result['http_code'] === 200 && isset($result['data']['token'])) {
        echo "✅ Authentication successful\n";
        return $result['data']['token'];
    } else {
        echo "❌ Authentication failed\n";
        echo "Response: " . $result['response'] . "\n";
        return null;
    }
}

// Start testing
echo "🚀 Starting Meal Request API Tests\n";
echo "Base URL: $baseUrl\n";

// Step 1: Authenticate user
$userToken = authenticate($userCredentials);
if (!$userToken) {
    die("❌ Cannot proceed without user authentication\n");
}

// Step 2: Authenticate admin
$adminToken = authenticate($adminCredentials);
if (!$adminToken) {
    echo "⚠️  Admin authentication failed, some tests will be skipped\n";
}

$userHeaders = ["Authorization: Bearer $userToken"];
$adminHeaders = $adminToken ? ["Authorization: Bearer $adminToken"] : [];

// Test 1: Create Meal Request
echo "\n📝 Testing: Create Meal Request\n";
$createData = [
    'date' => '2025-07-15',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test meal request from API testing'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData, $userHeaders);
logTest('Create Meal Request', $result);

$createdRequestId = null;
if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
    $createdRequestId = $result['data']['data']['id'];
    echo "✅ Created meal request with ID: $createdRequestId\n";
}

// Test 2: Get My Requests
echo "\n📋 Testing: Get My Requests\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, $userHeaders);
logTest('Get My Requests', $result);

// Test 3: Get My Requests with filters
echo "\n📋 Testing: Get My Requests with Filters\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests?status=0&per_page=10", null, $userHeaders);
logTest('Get My Requests with Filters', $result);

if ($createdRequestId) {
    // Test 4: Update Meal Request
    echo "\n✏️ Testing: Update Meal Request\n";
    $updateData = [
        'date' => '2025-07-16',
        'breakfast' => 0,
        'lunch' => 1,
        'dinner' => 1,
        'comment' => 'Updated test meal request'
    ];

    $result = makeRequest('PUT', "$baseUrl/meal-request/$createdRequestId/update", $updateData, $userHeaders);
    logTest('Update Meal Request', $result);

    // Test 5: Get Single Request (User)
    echo "\n🔍 Testing: Get Single Request Details\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/$createdRequestId", null, $userHeaders);
    logTest('Get Single Request Details', $result);
}

// Admin Tests
if ($adminToken && $createdRequestId) {
    echo "\n👑 ADMIN TESTS\n";

    // Test 6: Get Pending Requests
    echo "\n📋 Testing: Get Pending Requests (Admin)\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/pending", null, $adminHeaders);
    logTest('Get Pending Requests (Admin)', $result);

    // Test 7: Get All Requests
    echo "\n📋 Testing: Get All Requests (Admin)\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/", null, $adminHeaders);
    logTest('Get All Requests (Admin)', $result);

    // Test 8: Get All Requests with Filters
    echo "\n📋 Testing: Get All Requests with Filters (Admin)\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/?status=0&per_page=10&search=test", null, $adminHeaders);
    logTest('Get All Requests with Filters (Admin)', $result);

    // Test 9: Get Single Request (Admin)
    echo "\n🔍 Testing: Get Single Request Details (Admin)\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/$createdRequestId", null, $adminHeaders);
    logTest('Get Single Request Details (Admin)', $result);

    // Test 10: Approve Meal Request
    echo "\n✅ Testing: Approve Meal Request\n";
    $approveData = [
        'comment' => 'Approved during API testing'
    ];

    $result = makeRequest('POST', "$baseUrl/meal-request/$createdRequestId/approve", $approveData, $adminHeaders);
    logTest('Approve Meal Request', $result);

    // Check if approval was successful
    if ($result['http_code'] === 200) {
        echo "✅ Meal request approved successfully\n";

        // Verify the meal was created
        if (isset($result['data']['data']['created_meal'])) {
            echo "✅ Meal record created successfully\n";
        } else {
            echo "⚠️  No meal record found in response\n";
        }
    }
}

// Test 11: Create another request for rejection test
echo "\n📝 Testing: Create Another Meal Request for Rejection\n";
$createData2 = [
    'date' => '2025-07-17',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 1,
    'comment' => 'Test meal request for rejection'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData2, $userHeaders);
logTest('Create Another Meal Request', $result);

$secondRequestId = null;
if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
    $secondRequestId = $result['data']['data']['id'];
    echo "✅ Created second meal request with ID: $secondRequestId\n";
}

if ($adminToken && $secondRequestId) {
    // Test 12: Reject Meal Request
    echo "\n❌ Testing: Reject Meal Request\n";
    $rejectData = [
        'rejected_reason' => 'Insufficient budget for this date - API testing'
    ];

    $result = makeRequest('POST', "$baseUrl/meal-request/$secondRequestId/reject", $rejectData, $adminHeaders);
    logTest('Reject Meal Request', $result);
}

// Test 13: Create a request for cancellation
echo "\n📝 Testing: Create Meal Request for Cancellation\n";
$createData3 = [
    'date' => '2025-07-18',
    'breakfast' => 0,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test meal request for cancellation'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData3, $userHeaders);
logTest('Create Meal Request for Cancellation', $result);

$thirdRequestId = null;
if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
    $thirdRequestId = $result['data']['data']['id'];
    echo "✅ Created third meal request with ID: $thirdRequestId\n";
}

if ($thirdRequestId) {
    // Test 14: Cancel Meal Request
    echo "\n🚫 Testing: Cancel Meal Request\n";
    $cancelData = [
        'reason' => 'Change of plans - API testing'
    ];

    $result = makeRequest('POST', "$baseUrl/meal-request/$thirdRequestId/cancel", $cancelData, $userHeaders);
    logTest('Cancel Meal Request', $result);

    // Test 15: Try to update cancelled request (should fail)
    echo "\n❌ Testing: Try to Update Cancelled Request (Should Fail)\n";
    $updateData = [
        'date' => '2025-07-19',
        'breakfast' => 1,
        'lunch' => 1,
        'dinner' => 1,
        'comment' => 'This should fail'
    ];

    $result = makeRequest('PUT', "$baseUrl/meal-request/$thirdRequestId/update", $updateData, $userHeaders);
    logTest('Try to Update Cancelled Request', $result);
}

// Test 16: Create a request for deletion
echo "\n📝 Testing: Create Meal Request for Deletion\n";
$createData4 = [
    'date' => '2025-07-19',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 1,
    'comment' => 'Test meal request for deletion'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData4, $userHeaders);
logTest('Create Meal Request for Deletion', $result);

$fourthRequestId = null;
if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
    $fourthRequestId = $result['data']['data']['id'];
    echo "✅ Created fourth meal request with ID: $fourthRequestId\n";
}

if ($fourthRequestId) {
    // Test 17: Delete Meal Request
    echo "\n🗑️ Testing: Delete Meal Request\n";
    $result = makeRequest('DELETE', "$baseUrl/meal-request/$fourthRequestId/delete", null, $userHeaders);
    logTest('Delete Meal Request', $result);
}

// Error Tests
echo "\n🔴 ERROR TESTS\n";

// Test 18: Invalid date format
echo "\n❌ Testing: Invalid Date Format\n";
$invalidData = [
    'date' => 'invalid-date',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 0,
    'comment' => 'Invalid date test'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $invalidData, $userHeaders);
logTest('Invalid Date Format', $result);

// Test 19: No meals selected
echo "\n❌ Testing: No Meals Selected\n";
$noMealsData = [
    'date' => '2025-07-20',
    'breakfast' => 0,
    'lunch' => 0,
    'dinner' => 0,
    'comment' => 'No meals selected test'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $noMealsData, $userHeaders);
logTest('No Meals Selected', $result);

// Test 20: Past date
echo "\n❌ Testing: Past Date\n";
$pastDateData = [
    'date' => '2025-01-01',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 0,
    'comment' => 'Past date test'
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $pastDateData, $userHeaders);
logTest('Past Date', $result);

// Test 21: Unauthorized request
echo "\n❌ Testing: Unauthorized Request\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, []);
logTest('Unauthorized Request', $result);

// Test 22: Non-existent request
echo "\n❌ Testing: Non-existent Request\n";
$result = makeRequest('GET', "$baseUrl/meal-request/999999", null, $userHeaders);
logTest('Non-existent Request', $result);

// Final Summary
echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 80) . "\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($test) {
    return $test['success'];
}));
$failedTests = $totalTests - $passedTests;

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✅\n";
echo "Failed: $failedTests ❌\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";

echo "\nDetailed Results:\n";
foreach ($testResults as $test) {
    $status = $test['success'] ? '✅' : '❌';
    echo "$status {$test['test']} (HTTP: {$test['http_code']})\n";
}

echo "\n🎉 Testing completed!\n";

// Generate JSON report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'base_url' => $baseUrl,
    'total_tests' => $totalTests,
    'passed_tests' => $passedTests,
    'failed_tests' => $failedTests,
    'success_rate' => round(($passedTests / $totalTests) * 100, 2),
    'tests' => $testResults
];

file_put_contents('meal-request-api-test-report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\n📄 Test report saved to: meal-request-api-test-report.json\n";

?>

if (!$userToken) {
    echo "\n❌ Cannot proceed without user token. Please check login credentials.\n";
    exit(1);
}

// Test 3: Create Meal Request
echo "\n3. Testing Create Meal Request...\n";
$createResponse = makeRequest('POST', '/meal-request/add', [
    'mess_user_id' => 1,
    'date' => '2025-07-15',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test meal request'
], $userToken);

if ($createResponse && isset($createResponse['success']) && $createResponse['success']) {
    $mealRequestId = $createResponse['data']['id'] ?? null;
    echo "   ✅ Meal request created successfully\n";
    echo "   ID: $mealRequestId\n";
    echo "   Response structure: " . json_encode($createResponse, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Create meal request failed\n";
    echo "   Response: " . json_encode($createResponse, JSON_PRETTY_PRINT) . "\n";
}

// Test 4: Get My Requests
echo "\n4. Testing Get My Requests...\n";
$myRequestsResponse = makeRequest('GET', '/meal-request/my-requests', null, $userToken);

if ($myRequestsResponse && isset($myRequestsResponse['success']) && $myRequestsResponse['success']) {
    echo "   ✅ My requests retrieved successfully\n";
    echo "   Total requests: " . count($myRequestsResponse['data']['data']) . "\n";
    echo "   Response structure: " . json_encode($myRequestsResponse, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Get my requests failed\n";
    echo "   Response: " . json_encode($myRequestsResponse, JSON_PRETTY_PRINT) . "\n";
}

// Test 5: Update Meal Request (if we have an ID)
if ($mealRequestId) {
    echo "\n5. Testing Update Meal Request...\n";
    $updateResponse = makeRequest('PUT', "/meal-request/$mealRequestId/update", [
        'date' => '2025-07-16',
        'breakfast' => 0,
        'lunch' => 1,
        'dinner' => 1,
        'comment' => 'Updated test meal request'
    ], $userToken);

    if ($updateResponse && isset($updateResponse['success']) && $updateResponse['success']) {
        echo "   ✅ Meal request updated successfully\n";
        echo "   Response structure: " . json_encode($updateResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Update meal request failed\n";
        echo "   Response: " . json_encode($updateResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 6: Get Single Request Details (if we have an ID)
if ($mealRequestId) {
    echo "\n6. Testing Get Single Request Details...\n";
    $singleResponse = makeRequest('GET', "/meal-request/$mealRequestId", null, $userToken);

    if ($singleResponse && isset($singleResponse['success']) && $singleResponse['success']) {
        echo "   ✅ Single request retrieved successfully\n";
        echo "   Response structure: " . json_encode($singleResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Get single request failed\n";
        echo "   Response: " . json_encode($singleResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 7: Admin - Get Pending Requests
if ($adminToken) {
    echo "\n7. Testing Admin - Get Pending Requests...\n";
    $pendingResponse = makeRequest('GET', '/meal-request/pending', null, $adminToken);

    if ($pendingResponse && isset($pendingResponse['success']) && $pendingResponse['success']) {
        echo "   ✅ Pending requests retrieved successfully\n";
        echo "   Total pending: " . count($pendingResponse['data']['data']) . "\n";
        echo "   Response structure: " . json_encode($pendingResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Get pending requests failed\n";
        echo "   Response: " . json_encode($pendingResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 8: Admin - Get All Requests
if ($adminToken) {
    echo "\n8. Testing Admin - Get All Requests...\n";
    $allRequestsResponse = makeRequest('GET', '/meal-request/', null, $adminToken);

    if ($allRequestsResponse && isset($allRequestsResponse['success']) && $allRequestsResponse['success']) {
        echo "   ✅ All requests retrieved successfully\n";
        echo "   Total requests: " . count($allRequestsResponse['data']['data']) . "\n";
        echo "   Response structure: " . json_encode($allRequestsResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Get all requests failed\n";
        echo "   Response: " . json_encode($allRequestsResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 9: Admin - Approve Request (if we have an ID)
if ($adminToken && $mealRequestId) {
    echo "\n9. Testing Admin - Approve Request...\n";
    $approveResponse = makeRequest('POST', "/meal-request/$mealRequestId/approve", [
        'comment' => 'Approved by admin in test'
    ], $adminToken);

    if ($approveResponse && isset($approveResponse['success']) && $approveResponse['success']) {
        echo "   ✅ Request approved successfully\n";
        echo "   Response structure: " . json_encode($approveResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Approve request failed\n";
        echo "   Response: " . json_encode($approveResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 10: Cancel Request (create a new one first)
echo "\n10. Testing Cancel Request...\n";
$cancelTestResponse = makeRequest('POST', '/meal-request/add', [
    'mess_user_id' => 1,
    'date' => '2025-07-17',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 1,
    'comment' => 'Test cancel request'
], $userToken);

if ($cancelTestResponse && isset($cancelTestResponse['success']) && $cancelTestResponse['success']) {
    $cancelId = $cancelTestResponse['data']['id'];
    echo "   ✅ Created test request for cancellation (ID: $cancelId)\n";

    $cancelResponse = makeRequest('POST', "/meal-request/$cancelId/cancel", [
        'reason' => 'Test cancellation reason'
    ], $userToken);

    if ($cancelResponse && isset($cancelResponse['success']) && $cancelResponse['success']) {
        echo "   ✅ Request cancelled successfully\n";
        echo "   Response structure: " . json_encode($cancelResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Cancel request failed\n";
        echo "   Response: " . json_encode($cancelResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 11: Delete Request (create a new one first)
echo "\n11. Testing Delete Request...\n";
$deleteTestResponse = makeRequest('POST', '/meal-request/add', [
    'mess_user_id' => 1,
    'date' => '2025-07-18',
    'breakfast' => 0,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test delete request'
], $userToken);

if ($deleteTestResponse && isset($deleteTestResponse['success']) && $deleteTestResponse['success']) {
    $deleteId = $deleteTestResponse['data']['id'];
    echo "   ✅ Created test request for deletion (ID: $deleteId)\n";

    $deleteResponse = makeRequest('DELETE', "/meal-request/$deleteId/delete", null, $userToken);

    if ($deleteResponse && isset($deleteResponse['success']) && $deleteResponse['success']) {
        echo "   ✅ Request deleted successfully\n";
        echo "   Response structure: " . json_encode($deleteResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Delete request failed\n";
        echo "   Response: " . json_encode($deleteResponse, JSON_PRETTY_PRINT) . "\n";
    }
}

// Test 12: Test Error Cases
echo "\n12. Testing Error Cases...\n";

// Test with invalid date
$errorResponse = makeRequest('POST', '/meal-request/add', [
    'mess_user_id' => 1,
    'date' => '2025-07-05', // Past date
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 0,
    'comment' => 'Test error case'
], $userToken);

if ($errorResponse && isset($errorResponse['success']) && !$errorResponse['success']) {
    echo "   ✅ Past date validation working\n";
    echo "   Error response: " . json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ Past date validation failed\n";
    echo "   Response: " . json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n";
}

// Test with no meals selected
$errorResponse2 = makeRequest('POST', '/meal-request/add', [
    'mess_user_id' => 1,
    'date' => '2025-07-20',
    'breakfast' => 0,
    'lunch' => 0,
    'dinner' => 0,
    'comment' => 'Test error case'
], $userToken);

if ($errorResponse2 && isset($errorResponse2['success']) && !$errorResponse2['success']) {
    echo "   ✅ No meals validation working\n";
    echo "   Error response: " . json_encode($errorResponse2, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ No meals validation failed\n";
    echo "   Response: " . json_encode($errorResponse2, JSON_PRETTY_PRINT) . "\n";
}

echo "\n📋 API Test Summary Complete!\n";
echo "Check the responses above to verify API documentation accuracy.\n";

/**
 * Make HTTP request using cURL
 */
function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;
    $ch = curl_init();

    // Set basic cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Set headers
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Set method and data
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
        case 'GET':
        default:
            // GET is default
            break;
    }

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        echo "   ❌ cURL Error: $error\n";
        return null;
    }

    if ($httpCode >= 400) {
        echo "   ❌ HTTP Error: $httpCode\n";
        echo "   Response: $response\n";
        return null;
    }

    return json_decode($response, true);
}

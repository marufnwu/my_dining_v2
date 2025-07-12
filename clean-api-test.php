#!/usr/bin/env php
<?php

/**
 * Clean Meal Request API Test
 * Tests all endpoints and saves results to file
 */

$baseUrl = 'http://127.0.0.1:8000/api';
$results = [];

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
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'data' => json_decode($response, true)
    ];
}

function logTest($testName, $method, $endpoint, $requestData, $result) {
    global $results;

    $testResult = [
        'test_name' => $testName,
        'method' => $method,
        'endpoint' => $endpoint,
        'request_data' => $requestData,
        'http_code' => $result['http_code'],
        'response_data' => $result['data'],
        'raw_response' => $result['response'],
        'success' => $result['http_code'] >= 200 && $result['http_code'] < 300,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $results[] = $testResult;

    echo "✓ " . $testName . " - HTTP " . $result['http_code'] . "\n";

    return $testResult;
}

echo "🚀 Starting Meal Request API Tests\n";
echo "Base URL: $baseUrl\n";

// Test 1: Authentication
echo "\n1. Authentication...\n";
$loginData = [
    'email' => 'maruf@email.com',
    'password' => '11111111'
];

$authResult = makeRequest('POST', "$baseUrl/auth/login", $loginData);
$authTest = logTest('User Login', 'POST', '/auth/login', $loginData, $authResult);

if ($authResult['http_code'] !== 200) {
    echo "❌ Authentication failed - stopping tests\n";
    exit(1);
}

$token = $authResult['data']['token'] ?? null;
if (!$token) {
    echo "❌ No token received - stopping tests\n";
    exit(1);
}

$headers = ["Authorization: Bearer $token"];
echo "✅ Authentication successful\n";

// Test 2: Get My Requests
echo "\n2. Get My Requests...\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, $headers);
logTest('Get My Requests', 'GET', '/meal-request/my-requests', null, $result);

// Test 3: Create Meal Request
echo "\n3. Create Meal Request...\n";
$createData = [
    'date' => '2025-07-25',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test API - ' . date('H:i:s')
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData, $headers);
$createTest = logTest('Create Meal Request', 'POST', '/meal-request/add', $createData, $result);

$createdRequestId = null;
if ($result['data'] && isset($result['data']['data']['id'])) {
    $createdRequestId = $result['data']['data']['id'];
    echo "   Created ID: $createdRequestId\n";
}

// Test 4: Get Single Request
if ($createdRequestId) {
    echo "\n4. Get Single Request...\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/$createdRequestId", null, $headers);
    logTest('Get Single Request', 'GET', "/meal-request/$createdRequestId", null, $result);

    // Test 5: Update Request
    echo "\n5. Update Meal Request...\n";
    $updateData = [
        'date' => '2025-07-26',
        'breakfast' => 0,
        'lunch' => 1,
        'dinner' => 1,
        'comment' => 'Updated Test API - ' . date('H:i:s')
    ];

    $result = makeRequest('PUT', "$baseUrl/meal-request/$createdRequestId/update", $updateData, $headers);
    logTest('Update Meal Request', 'PUT', "/meal-request/$createdRequestId/update", $updateData, $result);

    // Test 6: Cancel Request
    echo "\n6. Cancel Meal Request...\n";
    $cancelData = [
        'reason' => 'Test cancellation - ' . date('H:i:s')
    ];

    $result = makeRequest('POST', "$baseUrl/meal-request/$createdRequestId/cancel", $cancelData, $headers);
    logTest('Cancel Meal Request', 'POST', "/meal-request/$createdRequestId/cancel", $cancelData, $result);
}

// Test 7: Error Cases
echo "\n7. Error Cases...\n";

// Invalid date
$invalidData = [
    'date' => 'invalid-date',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 0
];
$result = makeRequest('POST', "$baseUrl/meal-request/add", $invalidData, $headers);
logTest('Invalid Date Format', 'POST', '/meal-request/add', $invalidData, $result);

// No meals selected
$noMealsData = [
    'date' => '2025-07-27',
    'breakfast' => 0,
    'lunch' => 0,
    'dinner' => 0
];
$result = makeRequest('POST', "$baseUrl/meal-request/add", $noMealsData, $headers);
logTest('No Meals Selected', 'POST', '/meal-request/add', $noMealsData, $result);

// Unauthorized access
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, []);
logTest('Unauthorized Access', 'GET', '/meal-request/my-requests', null, $result);

// Non-existent request
$result = makeRequest('GET', "$baseUrl/meal-request/999999", null, $headers);
logTest('Non-existent Request', 'GET', '/meal-request/999999', null, $result);

echo "\n🎉 All tests completed!\n";

// Generate summary
$totalTests = count($results);
$passedTests = count(array_filter($results, function($test) {
    return $test['success'];
}));
$failedTests = $totalTests - $passedTests;

echo "\n📊 SUMMARY:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

// Save detailed results
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'base_url' => $baseUrl,
    'summary' => [
        'total_tests' => $totalTests,
        'passed_tests' => $passedTests,
        'failed_tests' => $failedTests,
        'success_rate' => round(($passedTests / $totalTests) * 100, 2)
    ],
    'tests' => $results
];

file_put_contents('api-test-results.json', json_encode($report, JSON_PRETTY_PRINT));
echo "📄 Detailed results saved to: api-test-results.json\n";

// Show failed tests
if ($failedTests > 0) {
    echo "\n❌ Failed Tests:\n";
    foreach ($results as $test) {
        if (!$test['success']) {
            echo "- " . $test['test_name'] . " (HTTP " . $test['http_code'] . ")\n";
        }
    }
}

?>

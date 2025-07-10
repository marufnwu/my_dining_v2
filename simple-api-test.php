#!/usr/bin/env php
<?php

/**
 * Simple Meal Request API Test
 * Basic test to verify API endpoints are working
 */

$baseUrl = 'http://127.0.0.1:8000/api';

echo "🔍 Testing Meal Request API\n";
echo "Base URL: $baseUrl\n\n";

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

// Test 1: Check API health
echo "1. Testing API Health...\n";
$result = makeRequest('GET', "$baseUrl/health");
echo "   HTTP Code: " . $result['http_code'] . "\n";
echo "   Response: " . $result['response'] . "\n";

// Test 2: Try to access meal requests without auth (should fail)
echo "\n2. Testing Unauthorized Access (Should Fail)...\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests");
echo "   HTTP Code: " . $result['http_code'] . "\n";
echo "   Response: " . $result['response'] . "\n";

// Test 3: Try user login
echo "\n3. Testing User Login...\n";
$loginData = [
    'email' => 'maruf@email.com',
    'password' => '11111111'
];
$result = makeRequest('POST', "$baseUrl/auth/login", $loginData);
echo "   HTTP Code: " . $result['http_code'] . "\n";
echo "   Response: " . $result['response'] . "\n";

$userToken = null;
if ($result['http_code'] === 200 && isset($result['data']['token'])) {
    $userToken = $result['data']['token'];
    echo "   ✅ Login successful! Token: " . substr($userToken, 0, 20) . "...\n";
} else {
    echo "   ❌ Login failed\n";
}

// Test 4: Try alternative login
if (!$userToken) {
    echo "\n4. Testing Alternative Login...\n";
    $loginData2 = [
        'email' => 'test@example.com',
        'password' => 'password'
    ];
    $result = makeRequest('POST', "$baseUrl/auth/login", $loginData2);
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . $result['response'] . "\n";

    if ($result['http_code'] === 200 && isset($result['data']['token'])) {
        $userToken = $result['data']['token'];
        echo "   ✅ Alternative login successful! Token: " . substr($userToken, 0, 20) . "...\n";
    }
}

// Test 5: If we have token, try to get meal requests
if ($userToken) {
    echo "\n5. Testing Get My Requests (Authenticated)...\n";
    $headers = ["Authorization: Bearer $userToken"];
    $result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, $headers);
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . $result['response'] . "\n";

    // Test 6: Try to create a meal request
    echo "\n6. Testing Create Meal Request...\n";
    $createData = [
        'date' => '2025-07-15',
        'breakfast' => 1,
        'lunch' => 1,
        'dinner' => 0,
        'comment' => 'Test meal request'
    ];
    $result = makeRequest('POST', "$baseUrl/meal-request/add", $createData, $headers);
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . $result['response'] . "\n";

    if ($result['http_code'] === 201 && isset($result['data']['data']['id'])) {
        $requestId = $result['data']['data']['id'];
        echo "   ✅ Created meal request with ID: $requestId\n";

        // Test 7: Try to get the created request
        echo "\n7. Testing Get Single Request...\n";
        $result = makeRequest('GET', "$baseUrl/meal-request/$requestId", null, $headers);
        echo "   HTTP Code: " . $result['http_code'] . "\n";
        echo "   Response: " . $result['response'] . "\n";
    }
}

echo "\n🎉 Basic API testing completed!\n";

?>

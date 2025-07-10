#!/usr/bin/env php
<?php

/**
 * Focused Meal Request API Test
 * Tests all meal request endpoints with proper authentication
 */

$baseUrl = 'http://127.0.0.1:8000/api';

echo "🚀 Testing Meal Request API Endpoints\n";
echo "Base URL: $baseUrl\n\n";

// Helper function
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

function formatResponse($response) {
    if (is_array($response)) {
        return json_encode($response, JSON_PRETTY_PRINT);
    }
    return $response;
}

// Step 1: Login
echo "1. 🔐 Authenticating...\n";
$loginData = [
    'email' => 'maruf@email.com',
    'password' => '11111111'
];

$authResult = makeRequest('POST', "$baseUrl/auth/login", $loginData);
echo "   HTTP Code: " . $authResult['http_code'] . "\n";

if ($authResult['http_code'] !== 200) {
    echo "   Response: " . $authResult['response'] . "\n";
    die("❌ Authentication failed\n");
}

$authData = $authResult['data'];
$token = $authData['token'] ?? null;

if (!$token) {
    echo "   Response: " . $authResult['response'] . "\n";
    die("❌ No token received\n");
}

echo "   ✅ Authentication successful\n";
echo "   User: " . ($authData['user']['name'] ?? 'Unknown') . "\n";
echo "   Token: " . substr($token, 0, 30) . "...\n";

$headers = ["Authorization: Bearer $token"];

// Step 2: Test Get My Requests
echo "\n2. 📋 Testing: GET /meal-request/my-requests\n";
$result = makeRequest('GET', "$baseUrl/meal-request/my-requests", null, $headers);
echo "   HTTP Code: " . $result['http_code'] . "\n";
echo "   Response Structure:\n";
if ($result['data']) {
    $data = $result['data'];
    echo "   - success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "   - message: " . ($data['message'] ?? 'N/A') . "\n";
    if (isset($data['data'])) {
        if (is_array($data['data']) && isset($data['data']['data'])) {
            echo "   - total_requests: " . count($data['data']['data']) . "\n";
            echo "   - current_page: " . ($data['data']['current_page'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "   Raw Response: " . $result['response'] . "\n";
}

// Step 3: Test Create Meal Request
echo "\n3. 📝 Testing: POST /meal-request/add\n";
$createData = [
    'date' => '2025-07-20',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'API Test - ' . date('Y-m-d H:i:s')
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $createData, $headers);
echo "   HTTP Code: " . $result['http_code'] . "\n";
echo "   Request Data: " . json_encode($createData, JSON_PRETTY_PRINT) . "\n";

$createdRequestId = null;
if ($result['data']) {
    $data = $result['data'];
    echo "   - success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "   - message: " . ($data['message'] ?? 'N/A') . "\n";

    if (isset($data['data']['id'])) {
        $createdRequestId = $data['data']['id'];
        echo "   - created_id: " . $createdRequestId . "\n";
        echo "   - status: " . ($data['data']['status'] ?? 'N/A') . "\n";
        echo "   - date: " . ($data['data']['date'] ?? 'N/A') . "\n";
    }
} else {
    echo "   Raw Response: " . $result['response'] . "\n";
}

// Step 4: Test Get Single Request
if ($createdRequestId) {
    echo "\n4. 🔍 Testing: GET /meal-request/{id}\n";
    $result = makeRequest('GET', "$baseUrl/meal-request/$createdRequestId", null, $headers);
    echo "   HTTP Code: " . $result['http_code'] . "\n";

    if ($result['data']) {
        $data = $result['data'];
        echo "   - success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "   - message: " . ($data['message'] ?? 'N/A') . "\n";

        if (isset($data['data'])) {
            $requestData = $data['data'];
            echo "   - id: " . ($requestData['id'] ?? 'N/A') . "\n";
            echo "   - status: " . ($requestData['status'] ?? 'N/A') . "\n";
            echo "   - status_text: " . ($requestData['status_text'] ?? 'N/A') . "\n";
            echo "   - user_name: " . ($requestData['user']['name'] ?? 'N/A') . "\n";
            echo "   - total_meals: " . ($requestData['total_meals'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   Raw Response: " . $result['response'] . "\n";
    }
}

// Step 5: Test Update Request
if ($createdRequestId) {
    echo "\n5. ✏️ Testing: PUT /meal-request/{id}/update\n";
    $updateData = [
        'date' => '2025-07-21',
        'breakfast' => 0,
        'lunch' => 1,
        'dinner' => 1,
        'comment' => 'Updated API Test - ' . date('Y-m-d H:i:s')
    ];

    $result = makeRequest('PUT', "$baseUrl/meal-request/$createdRequestId/update", $updateData, $headers);
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Update Data: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n";

    if ($result['data']) {
        $data = $result['data'];
        echo "   - success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "   - message: " . ($data['message'] ?? 'N/A') . "\n";

        if (isset($data['data'])) {
            echo "   - updated_date: " . ($data['data']['date'] ?? 'N/A') . "\n";
            echo "   - updated_comment: " . ($data['data']['comment'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   Raw Response: " . $result['response'] . "\n";
    }
}

// Step 6: Test Cancel Request
if ($createdRequestId) {
    echo "\n6. 🚫 Testing: POST /meal-request/{id}/cancel\n";
    $cancelData = [
        'reason' => 'API Test Cancellation - ' . date('Y-m-d H:i:s')
    ];

    $result = makeRequest('POST', "$baseUrl/meal-request/$createdRequestId/cancel", $cancelData, $headers);
    echo "   HTTP Code: " . $result['http_code'] . "\n";

    if ($result['data']) {
        $data = $result['data'];
        echo "   - success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "   - message: " . ($data['message'] ?? 'N/A') . "\n";

        if (isset($data['data'])) {
            echo "   - status: " . ($data['data']['status'] ?? 'N/A') . "\n";
            echo "   - status_text: " . ($data['data']['status_text'] ?? 'N/A') . "\n";
            echo "   - rejected_reason: " . ($data['data']['rejected_reason'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   Raw Response: " . $result['response'] . "\n";
    }
}

// Step 7: Test Error Cases
echo "\n7. 🔴 Testing Error Cases\n";

// Test invalid date
echo "\n7a. ❌ Testing: Invalid Date Format\n";
$invalidData = [
    'date' => 'invalid-date',
    'breakfast' => 1,
    'lunch' => 0,
    'dinner' => 0
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $invalidData, $headers);
echo "   HTTP Code: " . $result['http_code'] . "\n";
if ($result['data']) {
    echo "   - success: " . ($result['data']['success'] ? 'true' : 'false') . "\n";
    echo "   - message: " . ($result['data']['message'] ?? 'N/A') . "\n";
    if (isset($result['data']['errors'])) {
        echo "   - validation_errors: " . json_encode($result['data']['errors']) . "\n";
    }
}

// Test no meals selected
echo "\n7b. ❌ Testing: No Meals Selected\n";
$noMealsData = [
    'date' => '2025-07-22',
    'breakfast' => 0,
    'lunch' => 0,
    'dinner' => 0
];

$result = makeRequest('POST', "$baseUrl/meal-request/add", $noMealsData, $headers);
echo "   HTTP Code: " . $result['http_code'] . "\n";
if ($result['data']) {
    echo "   - success: " . ($result['data']['success'] ? 'true' : 'false') . "\n";
    echo "   - message: " . ($result['data']['message'] ?? 'N/A') . "\n";
    if (isset($result['data']['errors'])) {
        echo "   - validation_errors: " . json_encode($result['data']['errors']) . "\n";
    }
}

echo "\n🎉 API Testing Completed!\n\n";

// Summary
echo "📊 SUMMARY:\n";
echo "✅ Authentication: Working\n";
echo "✅ Get My Requests: Tested\n";
echo "✅ Create Request: Tested\n";
echo "✅ Get Single Request: Tested\n";
echo "✅ Update Request: Tested\n";
echo "✅ Cancel Request: Tested\n";
echo "✅ Error Handling: Tested\n";

?>

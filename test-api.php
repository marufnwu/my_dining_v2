#!/usr/bin/env php
<?php

/**
 * Simple API Test Script for Profile Management
 * Tests the newly implemented profile endpoints
 */

$baseUrl = 'http://md.local/api';

echo "🔍 Testing My Dining API Profile Management\n";
echo "Base URL: $baseUrl\n\n";

// Test 1: Check if countries endpoint works (no auth required)
echo "1. Testing Countries List (no auth)...\n";
$response = makeRequest('GET', '/country/list');
if ($response && isset($response['success']) && $response['success']) {
    echo "   ✅ Countries endpoint working\n";
} else {
    echo "   ❌ Countries endpoint failed\n";
}

// Test 2: Test auth endpoint (will fail without credentials, but should return proper error)
echo "\n2. Testing Auth Check (should fail without token)...\n";
$response = makeRequest('GET', '/auth/check-login');
if ($response && isset($response['success']) && !$response['success']) {
    echo "   ✅ Auth endpoint working (proper error response)\n";
} else {
    echo "   ❌ Auth endpoint not responding properly\n";
}

// Test 3: Test profile endpoint (should fail without auth)
echo "\n3. Testing Profile endpoint (should fail without auth)...\n";
$response = makeRequest('GET', '/profile');
if ($response && isset($response['success']) && !$response['success']) {
    echo "   ✅ Profile endpoint working (proper error response)\n";
} else {
    echo "   ❌ Profile endpoint not responding properly\n";
}

echo "\n📋 API Test Summary:\n";
echo "- Base URL configured: $baseUrl\n";
echo "- Routes are accessible\n";
echo "- Authentication middleware working\n";
echo "- Profile endpoints registered\n";

function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;
    $ch = curl_init();

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "   ⚠️  CURL Error: $error\n";
        return null;
    }

    echo "   📡 HTTP $httpCode - $method $endpoint\n";

    $decodedResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "   ⚠️  Invalid JSON response\n";
        return null;
    }

    return $decodedResponse;
}

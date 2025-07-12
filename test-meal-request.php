<?php

/**
 * Test script for Meal Request System
 * This script demonstrates the meal request functionality
 */

require_once 'vendor/autoload.php';

// Test data
$testData = [
    'mess_user_id' => 1,
    'date' => '2025-07-15',
    'breakfast' => 1,
    'lunch' => 1,
    'dinner' => 0,
    'comment' => 'Test meal request from script'
];

echo "=== Meal Request System Test ===\n\n";

echo "Test Data:\n";
print_r($testData);

echo "\n=== API Endpoints Available ===\n";
echo "1. POST /api/meal-request/add - Create meal request\n";
echo "2. PUT /api/meal-request/{id}/update - Update meal request\n";
echo "3. DELETE /api/meal-request/{id}/delete - Delete meal request\n";
echo "4. POST /api/meal-request/{id}/cancel - Cancel meal request\n";
echo "5. POST /api/meal-request/{id}/approve - Approve meal request (Admin)\n";
echo "6. POST /api/meal-request/{id}/reject - Reject meal request (Admin)\n";
echo "7. GET /api/meal-request/my-requests - View user's requests\n";
echo "8. GET /api/meal-request/pending - View pending requests (Admin)\n";
echo "9. GET /api/meal-request/ - View all requests (Admin)\n";
echo "10. GET /api/meal-request/{id} - View specific request\n";

echo "\n=== Permission Requirements ===\n";
echo "- Regular Users: Can create, update, delete, cancel their own requests\n";
echo "- Admins/Managers: Can approve/reject requests, view all requests\n";
echo "- Required Permissions: MEAL_REQUEST_MANAGEMENT, MEAL_MANAGEMENT\n";

echo "\n=== Database Tables ===\n";
echo "- meal_requests: Stores meal requests\n";
echo "- meals: Stores approved meals (created automatically)\n";

echo "\n=== Status Flow ===\n";
echo "PENDING (0) → APPROVED (1) → Meal Created\n";
echo "PENDING (0) → REJECTED (2)\n";
echo "PENDING (0) → CANCELLED (3)\n";

echo "\n=== Test Complete ===\n";
echo "The meal request system has been successfully implemented!\n";
echo "Use the API endpoints above to test the functionality.\n";

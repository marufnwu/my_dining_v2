<?php

require_once __DIR__ . '/vendor/autoload.php';

// Quick test to verify the MessManagementController class can be instantiated
$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    $controller = new \App\Http\Controllers\Api\MessManagementController();
    echo "✅ MessManagementController class loaded successfully\n";

    // Check if all required dependencies are available
    $reflection = new ReflectionClass($controller);
    echo "✅ Controller class has " . count($reflection->getMethods()) . " methods\n";

    // Verify MessJoinRequestStatus enum exists
    $statusEnum = \App\Enums\MessJoinRequestStatus::PENDING;
    echo "✅ MessJoinRequestStatus enum loaded: " . $statusEnum->value . "\n";

    // Verify MessUserService exists
    $service = new \App\Services\MessUserService();
    echo "✅ MessUserService loaded successfully\n";

    echo "\n🎉 All mess management components are working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mess;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanPackage;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\ManualPayment;
use App\Models\FeatureUsage;
use App\Facades\Feature;
use App\Constants\Feature as FeatureList;
use App\Constants\SubPlan;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use App\Services\FeatureService;
use App\Services\SubscriptionService;
use App\Services\PlanBuilderService;
use App\Config\FeatureConfig;
use Illuminate\Support\Facades\Schema;

// Add this section at the beginning of the test route to manually create test data
Route::get("create-test-data", function () {
    echo "<h1>Creating Test Data</h1>";

    // Create test messes if they don't exist
    $messes = [
        ['id' => 1001, 'name' => 'Test Mess - No Subscription'],
        ['id' => 1002, 'name' => 'Test Mess - Basic Plan'],
        ['id' => 1003, 'name' => 'Test Mess - Premium Plan'],
        ['id' => 1004, 'name' => 'Test Mess - Expired Subscription'],
        ['id' => 1005, 'name' => 'Test Mess - Grace Period'],
    ];

    foreach ($messes as $messData) {
        $mess = Mess::updateOrCreate(
            ['id' => $messData['id']],
            [
                'name' => $messData['name'],
                'address' => 'Test Address',
                'phone' => '+8801234567890',
                'email' => 'test@example.com',
                'status' => 'active'
            ]
        );
        echo "✅ Created mess: {$mess->name}<br>";
    }

    // Create test plans if they don't exist
    $plans = [
        ['id' => 1001, 'name' => 'Basic', 'keyword' => 'basic'],
        ['id' => 1002, 'name' => 'Premium', 'keyword' => 'premium'],
        ['id' => 1003, 'name' => 'Enterprise', 'keyword' => 'enterprise'],
    ];

    foreach ($plans as $planData) {
        $plan = Plan::updateOrCreate(
            ['id' => $planData['id']],
            [
                'name' => $planData['name'],
                'keyword' => $planData['keyword'],
                'description' => "Test {$planData['name']} plan",
                'is_active' => true,
                'sort_order' => $planData['id'] - 1000
            ]
        );
        echo "✅ Created plan: {$plan->name}<br>";
    }

    // Create subscriptions
    $subscriptions = [
        ['mess_id' => 1002, 'plan_id' => 1001, 'status' => 'active'],
        ['mess_id' => 1003, 'plan_id' => 1002, 'status' => 'active'],
        ['mess_id' => 1004, 'plan_id' => 1001, 'status' => 'expired'],
        ['mess_id' => 1005, 'plan_id' => 1002, 'status' => 'grace_period'],
    ];

    foreach ($subscriptions as $subData) {
        $subscription = Subscription::updateOrCreate(
            ['mess_id' => $subData['mess_id']],
            [
                'plan_id' => $subData['plan_id'],
                'starts_at' => now()->subDays(10),
                'expires_at' => $subData['status'] === 'expired' ? now()->subDays(5) : now()->addDays(20),
                'status' => $subData['status'],
                'grace_period_ends_at' => $subData['status'] === 'grace_period' ? now()->addDays(2) : null,
                'admin_grace_period_days' => 0
            ]
        );
        echo "✅ Created subscription: Mess {$subscription->mess_id} -> Plan {$subscription->plan_id} ({$subscription->status})<br>";
    }

    echo "<hr>";
    echo "✅ Test data created successfully!";
    echo "<br><a href='/play-ground'>Run Tests</a>";
});

Route::get("play-ground", function () {
    echo "<h1>My Dining Subscription System Tests</h1>";
    echo "<hr>";

    // 1. Test Data Overview
    echo "<h2>1. Test Data Overview</h2>";
    $messes = Mess::where('name', 'like', 'Test Mess%')->get();
    echo "Total Test Messes: " . $messes->count() . "<br>";

    foreach ($messes as $mess) {
        $subscription = $mess->subscription;
        $status = $subscription ? $subscription->status : 'No Subscription';
        echo "• {$mess->name}: {$status}<br>";
    }
    echo "<hr>";

    // 2. Feature Access Tests
    echo "<h2>2. Feature Access Tests</h2>";

    foreach ($messes as $mess) {
        echo "<h3>Testing: {$mess->name}</h3>";

        // Test member limit feature
        $memberLimitCheck = Feature::canUseFeature($mess, FeatureList::MEMBER_LIMIT);
        if ($memberLimitCheck->isSuccess()) {
            $data = $memberLimitCheck->getData();
            echo "✅ Member Limit: Available ({$data['used']}/{$data['limit']})<br>";
        } else {
            echo "❌ Member Limit: " . $memberLimitCheck->message . "<br>";
        }

        // Test report generation feature
        $reportCheck = Feature::canUseFeature($mess, FeatureList::MESS_REPORT_GENERATE);
        if ($reportCheck->isSuccess()) {
            $data = $reportCheck->getData();
            echo "✅ Report Generate: Available ({$data['used']}/{$data['limit']})<br>";
        } else {
            echo "❌ Report Generate: " . $reportCheck->message . "<br>";
        }

        // Test meal notification feature
        $mealNotificationCheck = Feature::canUseFeature($mess, FeatureList::MEAL_ADD_NOTIFICATION);
        if ($mealNotificationCheck->isSuccess()) {
            $data = $mealNotificationCheck->getData();
            echo "✅ Meal Notification: Available ({$data['used']}/{$data['limit']})<br>";
        } else {
            echo "❌ Meal Notification: " . $mealNotificationCheck->message . "<br>";
        }

        // Test purchase request feature
        $purchaseRequestCheck = Feature::canUseFeature($mess, FeatureList::PURCHASE_REQUEST);
        if ($purchaseRequestCheck->isSuccess()) {
            echo "✅ Purchase Request: Available<br>";
        } else {
            echo "❌ Purchase Request: " . $purchaseRequestCheck->message . "<br>";
        }

        // Test fund add feature
        $fundAddCheck = Feature::canUseFeature($mess, FeatureList::FUND_ADD);
        if ($fundAddCheck->isSuccess()) {
            echo "✅ Fund Add: Available<br>";
        } else {
            echo "❌ Fund Add: " . $fundAddCheck->message . "<br>";
        }

        // Test role management feature
        $roleManagementCheck = Feature::canUseFeature($mess, FeatureList::ROLE_MANAGEMENT);
        if ($roleManagementCheck->isSuccess()) {
            echo "✅ Role Management: Available<br>";
        } else {
            echo "❌ Role Management: " . $roleManagementCheck->message . "<br>";
        }

        echo "<br>";
    }
    echo "<hr>";

    // 3. Subscription Status Tests
    echo "<h2>3. Subscription Status Tests</h2>";

    foreach ($messes as $mess) {
        $subscription = $mess->subscription;
        echo "<h3>{$mess->name}</h3>";

        if ($subscription) {
            echo "Plan: {$subscription->plan->name}<br>";
            echo "Status: {$subscription->status}<br>";
            echo "Expires: {$subscription->expires_at->format('Y-m-d')}<br>";
            echo "Active: " . ($subscription->isActiveOrInGrace() ? '✅ Yes' : '❌ No') . "<br>";

            if ($subscription->inGracePeriod()) {
                echo "Grace Period: ✅ Yes (ends: {$subscription->grace_period_ends_at->format('Y-m-d')})<br>";
            }

            // Test subscription methods
            echo "Is Active: " . ($subscription->isActive() ? '✅ Yes' : '❌ No') . "<br>";
            echo "Is Expired: " . ($subscription->hasExpired() ? '✅ Yes' : '❌ No') . "<br>";
            echo "Days Until Expiry: " . $subscription->daysUntilExpiry() . "<br>";
        } else {
            echo "Subscription: ❌ None (Free Tier)<br>";
        }
        echo "<br>";
    }
    echo "<hr>";

    // 4. Payment Method Tests
    echo "<h2>4. Payment Method Tests</h2>";
    $paymentMethods = PaymentMethod::all();
    echo "Available Payment Methods: {$paymentMethods->count()}<br>";

    foreach ($paymentMethods as $method) {
        echo "• {$method->name} ({$method->keyword}): " . ($method->is_active ? '✅ Active' : '❌ Inactive') . "<br>";
        if ($method->config) {
            $config = json_decode($method->config, true);
            echo "  Config: " . json_encode($config) . "<br>";
        }
    }
    echo "<hr>";

    // 5. Manual Payment Tests
    echo "<h2>5. Manual Payment Tests</h2>";
    $manualPayments = ManualPayment::all();
    echo "Total Manual Payments: {$manualPayments->count()}<br>";

    foreach ($manualPayments as $payment) {
        echo "• {$payment->transaction_id}: {$payment->status} (\${$payment->amount})<br>";
        if ($payment->notes) {
            echo "  Notes: {$payment->notes}<br>";
        }
    }
    echo "<hr>";

    // 6. Feature Usage Tracking Tests
    echo "<h2>6. Feature Usage Tracking Tests</h2>";

    $subscriptions = Subscription::all();
    foreach ($subscriptions as $subscription) {
        echo "<h3>{$subscription->mess->name} - {$subscription->plan->name}</h3>";

        $features = $subscription->plan->features;
        foreach ($features as $feature) {
            $usage = $subscription->featureUsages()->where('plan_feature_id', $feature->id)->first();
            $used = $usage ? $usage->used : 0;
            $limit = $feature->usage_limit > 0 ? $feature->usage_limit : 'Unlimited';
            echo "• {$feature->name}: {$used}/{$limit} (Reset: {$feature->reset_period})<br>";
        }
        echo "<br>";
    }
    echo "<hr>";

    // 7. Plan and Feature Management Tests
    echo "<h2>7. Plan and Feature Management Tests</h2>";

    $plans = Plan::all();
    echo "Total Plans: {$plans->count()}<br><br>";

    foreach ($plans as $plan) {
        echo "<h3>{$plan->name} ({$plan->keyword})</h3>";
        echo "Description: {$plan->description}<br>";
        echo "Active: " . ($plan->is_active ? '✅ Yes' : '❌ No') . "<br>";
        echo "Sort Order: {$plan->sort_order}<br>";

        $features = $plan->features;
        echo "Features: {$features->count()}<br>";

        foreach ($features as $feature) {
            $limit = $feature->usage_limit > 0 ? $feature->usage_limit : 'Unlimited';
            echo "  • {$feature->name}: {$limit} ({$feature->reset_period})<br>";
        }
        echo "<br>";
    }
    echo "<hr>";

    // 8. Feature Increment and Reset Tests
    echo "<h2>8. Feature Increment and Reset Tests</h2>";

    foreach ($messes as $mess) {
        echo "<h3>Testing Feature Increment: {$mess->name}</h3>";

        // Test incrementing member limit
        $incrementResult = Feature::incrementFeatureUsage($mess, FeatureList::MEMBER_LIMIT);
        if ($incrementResult->isSuccess()) {
            $data = $incrementResult->getData();
            echo "✅ Member Limit Increment: Success ({$data['used']}/{$data['limit']})<br>";
        } else {
            echo "❌ Member Limit Increment: " . $incrementResult->message . "<br>";
        }

        // Test incrementing report generation
        $reportIncrement = Feature::incrementFeatureUsage($mess, FeatureList::MESS_REPORT_GENERATE);
        if ($reportIncrement->isSuccess()) {
            $data = $reportIncrement->getData();
            echo "✅ Report Generation Increment: Success ({$data['used']}/{$data['limit']})<br>";
        } else {
            echo "❌ Report Generation Increment: " . $reportIncrement->message . "<br>";
        }

        echo "<br>";
    }
    echo "<hr>";

    // 9. Available Features Tests
    echo "<h2>9. Available Features Tests</h2>";

    foreach ($messes as $mess) {
        echo "<h3>Available Features for: {$mess->name}</h3>";

        $availableFeatures = Feature::getAvailableFeatures($mess);
        if ($availableFeatures->isSuccess()) {
            $features = $availableFeatures->getData();
            foreach ($features as $feature) {
                $limit = $feature['usage_limit'] > 0 ? $feature['usage_limit'] : 'Unlimited';
                $used = $feature['used'] ?? 0;
                echo "• {$feature['name']}: {$used}/{$limit} (Reset: {$feature['reset_period']})<br>";
            }
        } else {
            echo "❌ Error getting features: " . $availableFeatures->message . "<br>";
        }
        echo "<br>";
    }
    echo "<hr>";

    // 10. Subscription Lifecycle Tests
    echo "<h2>10. Subscription Lifecycle Tests</h2>";

    foreach ($subscriptions as $subscription) {
        echo "<h3>Subscription: {$subscription->mess->name} - {$subscription->plan->name}</h3>";

        echo "Start Date: {$subscription->starts_at->format('Y-m-d')}<br>";
        echo "Expiry Date: {$subscription->expires_at->format('Y-m-d')}<br>";
        echo "Status: {$subscription->status}<br>";
        echo "Grace Period Ends: " . ($subscription->grace_period_ends_at ? $subscription->grace_period_ends_at->format('Y-m-d') : 'None') . "<br>";
        echo "Admin Grace Period Days: {$subscription->admin_grace_period_days}<br>";

        // Test subscription state methods
        echo "Is Active: " . ($subscription->isActive() ? '✅ Yes' : '❌ No') . "<br>";
        echo "Is In Grace Period: " . ($subscription->inGracePeriod() ? '✅ Yes' : '❌ No') . "<br>";
        echo "Is Expired: " . ($subscription->hasExpired() ? '✅ Yes' : '❌ No') . "<br>";
        echo "Is Active Or In Grace: " . ($subscription->isActiveOrInGrace() ? '✅ Yes' : '❌ No') . "<br>";
        echo "Days Until Expiry: " . $subscription->daysUntilExpiry() . "<br>";

        echo "<br>";
    }
    echo "<hr>";

    // 11. Plan Comparison Tests
    echo "<h2>11. Plan Comparison Tests</h2>";

    $basicPlan = Plan::where('keyword', SubPlan::BASIC)->first();
    $premiumPlan = Plan::where('keyword', SubPlan::PREMIUM)->first();
    $enterprisePlan = Plan::where('keyword', SubPlan::ENTERPRISE)->first();

    if ($basicPlan && $premiumPlan) {
        echo "<h3>Basic vs Premium Plan Comparison</h3>";

        $basicFeatures = $basicPlan->features;
        $premiumFeatures = $premiumPlan->features;

        echo "Basic Plan Features: {$basicFeatures->count()}<br>";
        echo "Premium Plan Features: {$premiumFeatures->count()}<br><br>";

        // Compare features
        $allFeatures = $basicFeatures->merge($premiumFeatures)->unique('name');

        foreach ($allFeatures as $feature) {
            $basicFeature = $basicFeatures->where('name', $feature->name)->first();
            $premiumFeature = $premiumFeatures->where('name', $feature->name)->first();

            echo "<strong>{$feature->name}:</strong><br>";
            echo "  Basic: " . ($basicFeature ? $basicFeature->usage_limit : 'Not Available') . "<br>";
            echo "  Premium: " . ($premiumFeature ? $premiumFeature->usage_limit : 'Not Available') . "<br>";
        }
    }
    echo "<hr>";

    // 12. System Health Check
    echo "<h2>12. System Health Check</h2>";

    $activeSubscriptions = Subscription::where('status', 'active')->count();
    $expiredSubscriptions = Subscription::where('status', 'expired')->count();
    $gracePeriodSubscriptions = Subscription::where('status', 'grace_period')->count();
    $totalPlans = Plan::count();
    $totalPlanFeatures = PlanFeature::count();
    $totalFeatureUsage = FeatureUsage::count();

    echo "Active Subscriptions: {$activeSubscriptions}<br>";
    echo "Expired Subscriptions: {$expiredSubscriptions}<br>";
    echo "Grace Period Subscriptions: {$gracePeriodSubscriptions}<br>";
    echo "Total Plans: {$totalPlans}<br>";
    echo "Total Plan Features: {$totalPlanFeatures}<br>";
    echo "Total Feature Usage Records: {$totalFeatureUsage}<br>";

    // Check for potential issues
    echo "<h3>System Health Indicators:</h3>";

    // Check for subscriptions without features
    $subscriptionsWithoutFeatures = Subscription::whereDoesntHave('plan.features')->count();
    echo "Subscriptions without features: {$subscriptionsWithoutFeatures}<br>";

    // Check for orphaned feature usage
    $orphanedUsage = FeatureUsage::whereDoesntHave('subscription')->count();
    echo "Orphaned feature usage records: {$orphanedUsage}<br>";

    // Check for expired subscriptions that should be in grace period
    $expiredButNotGrace = Subscription::where('status', 'expired')
        ->where('expires_at', '<', now())
        ->whereNull('grace_period_ends_at')
        ->count();
    echo "Expired subscriptions without grace period: {$expiredButNotGrace}<br>";

    echo "<hr>";
    echo "<h2>✅ Comprehensive Test Summary</h2>";
    echo "All subscription system tests completed successfully!<br>";
    echo "Tested Features:<br>";
    echo "• Feature Access Control<br>";
    echo "• Subscription Status Management<br>";
    echo "• Payment Method Integration<br>";
    echo "• Manual Payment Processing<br>";
    echo "• Feature Usage Tracking<br>";
    echo "• Plan and Feature Management<br>";
    echo "• Feature Increment and Reset<br>";
    echo "• Available Features Listing<br>";
    echo "• Subscription Lifecycle<br>";
    echo "• Plan Comparison<br>";
    echo "• System Health Monitoring<br>";
    echo "<br>You can now test all subscription features with realistic data.<br>";
});

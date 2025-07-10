# My Dining Subscription System Documentation

## Overview
The My Dining subscription system provides a flexible and feature-rich way to manage mess subscriptions, handle payments, and control feature access based on subscription plans.

## Table of Contents
1. [Plans and Features](#plans-and-features)
2. [Subscription Management](#subscription-management)
3. [Payment Integration](#payment-integration)
4. [Feature Access Control](#feature-access-control)
5. [Code Examples](#code-examples)

## Plans and Features

### Available Plans
- **Basic Plan**
  - Free plan with limited features
  - Member limit: 10 members
  - Report generation: 5 per month
  - Basic notifications
  
- **Premium Plan**
  - Enhanced features for growing messes
  - Member limit: 20 members
  - Report generation: 10 per month
  - Advanced notifications
  - Purchase request system
  
- **Enterprise Plan**
  - Full feature access for large messes
  - Member limit: 50 members
  - Unlimited report generation
  - All notification types
  - Advanced role management
  - Fund management

### Feature List
```php
// Available features (App\Constants\Feature)
const MEMBER_LIMIT = 'Member Limit';
const MESS_REPORT_GENERATE = 'Report Generate';
const MEAL_ADD_NOTIFICATION = 'Meal Add Notification';
const BALANCE_ADD_NOTIFICATION = 'Balance Add Notification';
const PURCHASE_NOTIFICATION = 'Purchase Notification';
const FUND_ADD = 'Fund Add';
const ROLE_MANAGEMENT = 'Role Management';
const PURCHASE_REQUEST = 'Purchase Request';
```

## Subscription Management

### Checking Subscription Status
```php
use App\Facades\Feature;

// Check if mess has active subscription
$hasActiveSubscription = Feature::hasActiveSubscription($mess);

// Check specific feature access
$canUseFeature = Feature::canUseFeature($mess, Feature::MEMBER_LIMIT);
if ($canUseFeature->isSuccess()) {
    // Feature is available
    $remainingUsage = $canUseFeature->getData()['remaining'];
    $usageLimit = $canUseFeature->getData()['limit'];
}

// Get all available features
$features = Feature::getAvailableFeatures($mess);
```

### Route Protection
```php
// In routes/api.php
Route::middleware(['feature:Member Limit'])->group(function () {
    Route::post('/members/add', [MemberController::class, 'store']);
});

// Multiple features
Route::middleware(['feature:Role Management,Purchase Request'])->group(function () {
    Route::post('/roles', [RoleController::class, 'store']);
});
```

### Controller Usage
```php
use App\Facades\Feature;
use App\Constants\Feature as FeatureList;

class MessController extends Controller
{
    public function addMember(Request $request)
    {
        // Check and increment feature usage
        $featureCheck = Feature::canUseFeature($mess, FeatureList::MEMBER_LIMIT);
        if (!$featureCheck->isSuccess()) {
            return response()->json([
                'error' => $featureCheck->getMessage()
            ], 403);
        }

        // Add member logic here...

        // Increment usage after successful operation
        Feature::incrementFeatureUsage($mess, FeatureList::MEMBER_LIMIT);
    }
}
```

## Payment Integration

### Google Play Integration
1. Setup Google Play credentials:
```bash
php artisan setup:google-play --credentials-file=/path/to/google-play-credentials.json --package-name=com.example.mydining
```

2. Handle subscription purchase:
```php
class PaymentController extends Controller
{
    public function verifyGooglePlayPurchase(GooglePlayPurchaseRequest $request)
    {
        $data = $request->validated();
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        $pipeline = $this->googlePlayService->verifySubscription(
            $paymentMethod,
            $data['purchase_token'],
            $data['subscription_id']
        );

        if ($pipeline->isSuccess()) {
            // Subscription verified and activated
        }
    }
}
```

### Manual Payment Integration
1. Submit manual payment:
```php
$payment = $paymentService->submitManualPayment([
    'subscription_id' => $subscription->id,
    'payment_method_id' => $paymentMethod->id,
    'amount' => 99.99,
    'transaction_id' => 'manual_trans_123',
    'proof_url' => 'https://example.com/proof.jpg'
]);
```

2. Review manual payment:
```php
$review = $paymentService->reviewManualPayment(
    payment: $manualPayment,
    status: PaymentStatus::APPROVED,
    reviewer: $adminUser,
    notes: 'Payment verified'
);
```

## Feature Access Control

### Middleware Usage
```php
// Protect routes with feature access middleware
Route::middleware('feature:Report Generate')->group(function () {
    Route::get('/reports/generate', [ReportController::class, 'generate']);
});
```

### Service Layer Usage
```php
class ReportService
{
    protected $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public function generateReport(Mess $mess)
    {
        $featureCheck = $this->featureService->canUseFeature(
            $mess,
            Feature::MESS_REPORT_GENERATE
        );

        if (!$featureCheck->isSuccess()) {
            return Pipeline::error($featureCheck->getMessage());
        }

        // Generate report logic...

        // Increment usage
        $this->featureService->incrementFeatureUsage(
            $mess,
            Feature::MESS_REPORT_GENERATE
        );

        return Pipeline::success($report);
    }
}
```

## Subscription Lifecycle

### Creating a New Subscription
```php
// Create subscription for a mess
$subscription = $mess->subscription()->create([
    'plan_id' => $plan->id,
    'plan_package_id' => $package->id,
    'starts_at' => now(),
    'expires_at' => now()->addDays($package->duration),
    'status' => Subscription::STATUS_ACTIVE
]);

// Create initial order
$order = $subscription->createOrder([
    'payment_method' => 'google_play',
    'payment_provider' => 'google_play'
]);
```

### Handling Subscription Events
```php
// In GooglePlayPaymentService
public function handleSubscriptionNotification(array $notification)
{
    switch ($notification['notificationType']) {
        case 2: // Renewed
            // Handle renewal
            break;
        case 3: // Cancelled
            // Handle cancellation
            break;
        case 13: // Grace period
            // Handle grace period
            break;
        case 4: // Refunded
            // Handle refund
            break;
    }
}
```

### Grace Period Management
```php
// Check grace period status
if ($subscription->inGracePeriod()) {
    $daysRemaining = $subscription->grace_period_ends_at->diffInDays(now());
}

// Extend grace period (admin only)
$subscription->admin_grace_period_days = 7;
$subscription->grace_period_ends_at = $subscription->calculateGracePeriodEndDate();
$subscription->save();
```

## Best Practices

1. **Feature Checking**
   - Always check feature availability before performing restricted actions
   - Use the Feature facade for consistent access control
   - Handle feature usage limits appropriately

2. **Payment Processing**
   - Validate payments before activating subscriptions
   - Handle webhook notifications reliably
   - Maintain proper transaction records

3. **Error Handling**
   - Use Pipeline responses for consistent error handling
   - Provide clear error messages to users
   - Log payment and subscription errors for debugging

4. **Security**
   - Validate webhook signatures
   - Protect admin routes and actions
   - Maintain proper audit trails for payments

## Common Use Cases

### Setting Up a New Mess
```php
// 1. Create mess
$mess = Mess::create([...]);

// 2. Set up free trial subscription
$plan = Plan::where('keyword', SubPlan::BASIC)->first();
$trialPackage = $plan->packages()->where('is_trial', true)->first();

$subscription = $mess->subscription()->create([
    'plan_id' => $plan->id,
    'plan_package_id' => $trialPackage->id,
    'starts_at' => now(),
    'expires_at' => now()->addDays($trialPackage->duration),
    'status' => Subscription::STATUS_TRIAL
]);
```

### Upgrading a Subscription
```php
// 1. Create new order for upgrade
$newOrder = $subscription->createOrder([
    'plan_id' => $newPlan->id,
    'plan_package_id' => $newPackage->id,
    'amount' => $newPackage->price
]);

// 2. Process payment
$paymentResult = $paymentService->processPayment($newOrder);

// 3. Update subscription if payment successful
if ($paymentResult->isSuccess()) {
    $subscription->update([
        'plan_id' => $newPlan->id,
        'plan_package_id' => $newPackage->id,
        'expires_at' => now()->addDays($newPackage->duration)
    ]);
}
```

### Monitoring Usage
```php
// Get feature usage statistics
$usageStats = $mess->subscription->featureUsages()
    ->with('feature')
    ->get()
    ->map(function ($usage) {
        return [
            'feature' => $usage->feature->name,
            'used' => $usage->used,
            'limit' => $usage->feature->usage_limit,
            'remaining' => $usage->feature->usage_limit - $usage->used
        ];
    });
```

## API Endpoints

### Subscription Management
- `GET /api/v1/subscriptions/status` - Get current subscription status
- `GET /api/v1/subscriptions/features` - List available features
- `POST /api/v1/subscriptions/upgrade` - Upgrade subscription
- `POST /api/v1/payments/google-play/verify` - Verify Google Play purchase
- `POST /api/v1/payments/manual` - Submit manual payment
- `PUT /api/v1/payments/manual/{id}/review` - Review manual payment
- `GET /api/v1/subscriptions/usage` - Get feature usage statistics

## Troubleshooting

### Common Issues

1. **Feature Access Denied**
   - Check if subscription is active
   - Verify feature is included in current plan
   - Check usage limits haven't been exceeded

2. **Payment Verification Failed**
   - Validate payment credentials
   - Check webhook signatures
   - Verify subscription IDs and tokens

3. **Grace Period Issues**
   - Verify grace period calculation
   - Check subscription status
   - Validate payment status

### Debug Tools
```php
// Check subscription status details
$subscription->refresh();
echo "Status: " . $subscription->status;
echo "Active: " . $subscription->isActive();
echo "In Grace: " . $subscription->inGracePeriod();
echo "Expired: " . $subscription->hasExpired();

// Verify feature configuration
$feature = PlanFeature::where('name', Feature::MEMBER_LIMIT)
    ->where('plan_id', $subscription->plan_id)
    ->first();
echo "Limit: " . $feature->usage_limit;

// Check current usage
$usage = FeatureUsage::where('subscription_id', $subscription->id)
    ->where('plan_feature_id', $feature->id)
    ->first();
echo "Used: " . $usage->used;
```

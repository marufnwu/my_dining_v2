<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mess;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanPackage;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\ManualPayment;
use App\Models\FeatureUsage;
use App\Constants\Feature as FeatureList;
use App\Constants\SubPlan;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

class SubscriptionTestSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🌱 Starting subscription test seeder...');

        try {
            // Clean up existing test data first
            $this->cleanupExistingTestData();

            $this->createTestMesses();
            $this->createTestPlans();
            $this->createTestPlanFeatures();
            $this->createTestSubscriptions();
            $this->createTestPaymentMethods();
            $this->createTestManualPayments();
            $this->createTestFeatureUsage();

            $this->command->info('✅ All test data created successfully!');
        } catch (\Exception $e) {
            $this->command->error('❌ Error: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function cleanupExistingTestData()
    {
        $this->command->info('Cleaning up existing test data...');

        // Delete in correct order
        FeatureUsage::whereHas('subscription', function($q) {
            $q->whereHas('mess', function($q2) {
                $q2->where('name', 'like', 'Test Mess%');
            });
        })->delete();

        ManualPayment::whereHas('subscription', function($q) {
            $q->whereHas('mess', function($q2) {
                $q2->where('name', 'like', 'Test Mess%');
            });
        })->delete();

        Subscription::whereHas('mess', function($q) {
            $q->where('name', 'like', 'Test Mess%');
        })->delete();

        Mess::where('name', 'like', 'Test Mess%')->delete();

        $this->command->info('✅ Existing test data cleaned up');
    }

    private function createTestMesses()
    {
        $this->command->info('Creating test messes...');

        // Create test messes with different subscription states
        $messes = [
            [
                'id' => 1001, // Use high IDs to avoid conflicts
                'name' => 'Test Mess - No Subscription',
                'address' => '123 Test Street',
                'phone' => '+8801234567890',
                'email' => 'test1@example.com',
                'status' => 'active'
            ],
            [
                'id' => 1002,
                'name' => 'Test Mess - Basic Plan',
                'address' => '456 Basic Street',
                'phone' => '+8801234567891',
                'email' => 'test2@example.com',
                'status' => 'active'
            ],
            [
                'id' => 1003,
                'name' => 'Test Mess - Premium Plan',
                'address' => '789 Premium Street',
                'phone' => '+8801234567892',
                'email' => 'test3@example.com',
                'status' => 'active'
            ],
            [
                'id' => 1004,
                'name' => 'Test Mess - Expired Subscription',
                'address' => '321 Expired Street',
                'phone' => '+8801234567893',
                'email' => 'test4@example.com',
                'status' => 'active'
            ],
            [
                'id' => 1005,
                'name' => 'Test Mess - Grace Period',
                'address' => '654 Grace Street',
                'phone' => '+8801234567894',
                'email' => 'test5@example.com',
                'status' => 'active'
            ]
        ];

        foreach ($messes as $messData) {
            Mess::updateOrCreate(
                ['id' => $messData['id']],
                $messData
            );
        }

        $this->command->info('✅ Test messes created');
    }

    private function createTestPlans()
    {
        $this->command->info('Creating test plans...');

        // Create plans
        $plans = [
            [
                'id' => 1001, // Use high IDs
                'name' => 'Basic',
                'keyword' => SubPlan::BASIC,
                'description' => 'Basic plan for small messes',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'id' => 1002,
                'name' => 'Premium',
                'keyword' => SubPlan::PREMIUM,
                'description' => 'Premium plan for growing messes',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'id' => 1003,
                'name' => 'Enterprise',
                'keyword' => SubPlan::ENTERPRISE,
                'description' => 'Enterprise plan for large messes',
                'is_active' => true,
                'sort_order' => 3
            ]
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['id' => $planData['id']],
                $planData
            );
        }

        $this->command->info('✅ Test plans created');
    }

    private function createTestPlanFeatures()
    {
        $this->command->info('Creating test plan features...');

        // Get plans
        $basicPlan = Plan::find(1001);
        $premiumPlan = Plan::find(1002);
        $enterprisePlan = Plan::find(1003);

        if (!$basicPlan || !$premiumPlan || !$enterprisePlan) {
            $this->command->warn('⚠️ Plans not found, skipping plan features');
            return;
        }

        // Define features for each plan
        $planFeatures = [
            // Basic Plan Features
            [
                'plan_id' => 1001,
                'name' => FeatureList::MEMBER_LIMIT,
                'description' => 'Maximum number of members',
                'is_countable' => true,
                'usage_limit' => 10,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],
            [
                'plan_id' => 1001,
                'name' => FeatureList::MESS_REPORT_GENERATE,
                'description' => 'Generate monthly reports',
                'is_countable' => true,
                'usage_limit' => 5,
                'is_active' => true,
                'reset_period' => 'monthly'
            ],
            [
                'plan_id' => 1001,
                'name' => FeatureList::MEAL_ADD_NOTIFICATION,
                'description' => 'Notifications for meal additions',
                'is_countable' => true,
                'usage_limit' => 50,
                'is_active' => true,
                'reset_period' => 'monthly'
            ],

            // Premium Plan Features
            [
                'plan_id' => 1002,
                'name' => FeatureList::MEMBER_LIMIT,
                'description' => 'Maximum number of members',
                'is_countable' => true,
                'usage_limit' => 20,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],
            [
                'plan_id' => 1002,
                'name' => FeatureList::MESS_REPORT_GENERATE,
                'description' => 'Generate monthly reports',
                'is_countable' => true,
                'usage_limit' => 10,
                'is_active' => true,
                'reset_period' => 'monthly'
            ],
            [
                'plan_id' => 1002,
                'name' => FeatureList::MEAL_ADD_NOTIFICATION,
                'description' => 'Notifications for meal additions',
                'is_countable' => true,
                'usage_limit' => 100,
                'is_active' => true,
                'reset_period' => 'monthly'
            ],
            [
                'plan_id' => 1002,
                'name' => FeatureList::PURCHASE_REQUEST,
                'description' => 'Submit purchase requests',
                'is_countable' => false,
                'usage_limit' => 0,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],

            // Enterprise Plan Features
            [
                'plan_id' => 1003,
                'name' => FeatureList::MEMBER_LIMIT,
                'description' => 'Maximum number of members',
                'is_countable' => true,
                'usage_limit' => 50,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],
            [
                'plan_id' => 1003,
                'name' => FeatureList::MESS_REPORT_GENERATE,
                'description' => 'Generate monthly reports',
                'is_countable' => true,
                'usage_limit' => -1, // Unlimited
                'is_active' => true,
                'reset_period' => 'monthly'
            ],
            [
                'plan_id' => 1003,
                'name' => FeatureList::MEAL_ADD_NOTIFICATION,
                'description' => 'Notifications for meal additions',
                'is_countable' => true,
                'usage_limit' => -1, // Unlimited
                'is_active' => true,
                'reset_period' => 'monthly'
            ],
            [
                'plan_id' => 1003,
                'name' => FeatureList::PURCHASE_REQUEST,
                'description' => 'Submit purchase requests',
                'is_countable' => false,
                'usage_limit' => 0,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],
            [
                'plan_id' => 1003,
                'name' => FeatureList::FUND_ADD,
                'description' => 'Add funds to mess account',
                'is_countable' => false,
                'usage_limit' => 0,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ],
            [
                'plan_id' => 1003,
                'name' => FeatureList::ROLE_MANAGEMENT,
                'description' => 'Manage user roles and permissions',
                'is_countable' => false,
                'usage_limit' => 0,
                'is_active' => true,
                'reset_period' => 'lifetime'
            ]
        ];

        foreach ($planFeatures as $featureData) {
            PlanFeature::updateOrCreate(
                [
                    'plan_id' => $featureData['plan_id'],
                    'name' => $featureData['name']
                ],
                $featureData
            );
        }

        $this->command->info('✅ Test plan features created');
    }

    private function createTestSubscriptions()
    {
        $this->command->info('Creating test subscriptions...');

        // Create subscriptions directly without relying on plan packages
        $subscriptions = [
            // Mess 1002 - Basic Plan (Active)
            [
                'mess_id' => 1002,
                'plan_id' => 1001,
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
                'status' => 'active',
                'grace_period_ends_at' => null,
                'admin_grace_period_days' => 0
            ],
            // Mess 1003 - Premium Plan (Active)
            [
                'mess_id' => 1003,
                'plan_id' => 1002,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
                'status' => 'active',
                'grace_period_ends_at' => null,
                'admin_grace_period_days' => 0
            ],
            // Mess 1004 - Expired Subscription
            [
                'mess_id' => 1004,
                'plan_id' => 1001,
                'starts_at' => now()->subDays(40),
                'expires_at' => now()->subDays(10),
                'status' => 'expired',
                'grace_period_ends_at' => null,
                'admin_grace_period_days' => 0
            ],
            // Mess 1005 - Grace Period
            [
                'mess_id' => 1005,
                'plan_id' => 1002,
                'starts_at' => now()->subDays(35),
                'expires_at' => now()->subDays(5),
                'status' => 'grace_period',
                'grace_period_ends_at' => now()->addDays(2),
                'admin_grace_period_days' => 7
            ]
        ];

        foreach ($subscriptions as $subscriptionData) {
            Subscription::updateOrCreate(
                ['mess_id' => $subscriptionData['mess_id']],
                $subscriptionData
            );
        }

        $this->command->info('✅ Test subscriptions created');
    }

    private function createTestPaymentMethods()
    {
        $this->command->info('Creating test payment methods...');

        $paymentMethods = [
            [
                'name' => 'Google Play',
                'keyword' => 'google_play',
                'description' => 'Google Play Store payments',
                'is_active' => true,
                'config' => json_encode([
                    'package_name' => 'com.example.mydining',
                    'credentials_file' => 'google-play-credentials.json'
                ])
            ],
            [
                'name' => 'Manual Payment',
                'keyword' => 'manual',
                'description' => 'Manual bank transfer or cash payment',
                'is_active' => true,
                'config' => json_encode([
                    'bank_account' => '1234567890',
                    'bank_name' => 'Test Bank'
                ])
            ],
            [
                'name' => 'Stripe',
                'keyword' => 'stripe',
                'description' => 'Stripe payment gateway',
                'is_active' => false,
                'config' => json_encode([
                    'publishable_key' => 'pk_test_...',
                    'secret_key' => 'sk_test_...'
                ])
            ]
        ];

        foreach ($paymentMethods as $methodData) {
            PaymentMethod::updateOrCreate(
                ['keyword' => $methodData['keyword']],
                $methodData
            );
        }

        $this->command->info('✅ Test payment methods created');
    }

    private function createTestManualPayments()
    {
        $this->command->info('Creating test manual payments...');

        $manualPaymentMethod = PaymentMethod::where('keyword', 'manual')->first();
        $basicSubscription = Subscription::where('mess_id', 1002)->first();

        if (!$manualPaymentMethod || !$basicSubscription) {
            $this->command->warn('⚠️ Skipping manual payments - missing dependencies');
            return;
        }

        $manualPayments = [
            [
                'subscription_id' => $basicSubscription->id,
                'payment_method_id' => $manualPaymentMethod->id,
                'amount' => 99.00,
                'transaction_id' => 'MANUAL_001',
                'proof_url' => 'https://example.com/proof1.jpg',
                'status' => PaymentStatus::PENDING,
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => null,
                'reviewer_id' => null,
                'notes' => 'Bank transfer proof submitted'
            ],
            [
                'subscription_id' => $basicSubscription->id,
                'payment_method_id' => $manualPaymentMethod->id,
                'amount' => 99.00,
                'transaction_id' => 'MANUAL_002',
                'proof_url' => 'https://example.com/proof2.jpg',
                'status' => PaymentStatus::APPROVED,
                'submitted_at' => now()->subDays(5),
                'reviewed_at' => now()->subDays(4),
                'reviewer_id' => 1,
                'notes' => 'Payment verified and approved'
            ]
        ];

        foreach ($manualPayments as $paymentData) {
            ManualPayment::updateOrCreate(
                ['transaction_id' => $paymentData['transaction_id']],
                $paymentData
            );
        }

        $this->command->info('✅ Test manual payments created');
    }

    private function createTestFeatureUsage()
    {
        $this->command->info('Creating test feature usage...');

        // Get subscriptions
        $basicSubscription = Subscription::where('mess_id', 1002)->first();
        $premiumSubscription = Subscription::where('mess_id', 1003)->first();

        // Get plan features
        $basicPlan = Plan::find(1001);
        $premiumPlan = Plan::find(1002);

        // Create feature usage for basic subscription
        if ($basicSubscription && $basicPlan) {
            $basicFeatures = $basicPlan->features;
            foreach ($basicFeatures as $feature) {
                FeatureUsage::updateOrCreate(
                    [
                        'subscription_id' => $basicSubscription->id,
                        'plan_feature_id' => $feature->id
                    ],
                    [
                        'used' => rand(0, $feature->usage_limit > 0 ? $feature->usage_limit : 10),
                        'reset_period' => $feature->reset_period ?? 'monthly',
                        'reset_at' => now()->addMonth(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        // Create feature usage for premium subscription
        if ($premiumSubscription && $premiumPlan) {
            $premiumFeatures = $premiumPlan->features;
            foreach ($premiumFeatures as $feature) {
                FeatureUsage::updateOrCreate(
                    [
                        'subscription_id' => $premiumSubscription->id,
                        'plan_feature_id' => $feature->id
                    ],
                    [
                        'used' => rand(0, $feature->usage_limit > 0 ? $feature->usage_limit : 20),
                        'reset_period' => $feature->reset_period ?? 'monthly',
                        'reset_at' => now()->addMonth(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        $this->command->info('✅ Test feature usage created');
    }
}

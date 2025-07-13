<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mess;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PaymentMethod;
use App\Models\ManualPayment;
use App\Models\FeatureUsage;

class CleanupTestData extends Command
{
    protected $signature = 'cleanup:test-data';
    protected $description = 'Clean up test data and start fresh';

    public function handle()
    {
        $this->info('🧹 Cleaning up test data...');

        // Delete in correct order to avoid foreign key constraints
        FeatureUsage::truncate();
        ManualPayment::truncate();
        Subscription::truncate();
        PlanFeature::truncate();
        PaymentMethod::truncate();

        // Delete test messes (keep existing ones)
        Mess::where('name', 'like', 'Test Mess%')->delete();

        // Delete test plans (keep existing ones)
        Plan::where('keyword', 'test_plan')->delete();

        $this->info('✅ Test data cleaned up successfully!');
    }
}

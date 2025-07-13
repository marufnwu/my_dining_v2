<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mess;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\PlanFeature;
use App\Models\PaymentMethod;

class DebugSubscriptionData extends Command
{
    protected $signature = 'debug:subscription-data';
    protected $description = 'Debug subscription data to see what exists';

    public function handle()
    {
        $this->info('🔍 Debugging subscription data...');

        // Check messes
        $this->info('MESSES:');
        $messes = Mess::where('name', 'like', 'Test Mess%')->get();
        foreach ($messes as $mess) {
            $this->line("  ID: {$mess->id}, Name: {$mess->name}");
        }

        // Check plans
        $this->info('PLANS:');
        $plans = Plan::all();
        foreach ($plans as $plan) {
            $this->line("  ID: {$plan->id}, Name: {$plan->name}, Keyword: {$plan->keyword}");
        }

        // Check subscriptions
        $this->info('SUBSCRIPTIONS:');
        $subscriptions = Subscription::all();
        foreach ($subscriptions as $sub) {
            $this->line("  ID: {$sub->id}, Mess ID: {$sub->mess_id}, Plan ID: {$sub->plan_id}, Status: {$sub->status}");
        }

        // Check plan features
        $this->info('PLAN FEATURES:');
        $features = PlanFeature::all();
        foreach ($features as $feature) {
            $this->line("  Plan ID: {$feature->plan_id}, Name: {$feature->name}, Limit: {$feature->usage_limit}");
        }

        // Check payment methods
        $this->info('PAYMENT METHODS:');
        $methods = PaymentMethod::all();
        foreach ($methods as $method) {
            $this->line("  ID: {$method->id}, Name: {$method->name}, Keyword: {$method->keyword}");
        }
    }
}

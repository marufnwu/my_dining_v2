<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Console\Command;

class SeedDefaultPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:seed-default {--force : Force seeding even if plans exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the default plan and all subscription plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Plan::count() > 0 && !$this->option('force')) {
            $this->error('Plans already exist. Use --force to override.');
            return 1;
        }

        $this->info('Seeding subscription plans...');

        try {
            $seeder = new PlanSeeder();
            $seeder->run();

            // Clear default plan cache
            Plan::clearDefaultPlanCache();

            $this->info('✅ Subscription plans seeded successfully!');
            $this->info('Plans created:');

            Plan::with('features', 'packages')->get()->each(function ($plan) {
                $this->line("  - {$plan->name} ({$plan->keyword})");
                $this->line("    Features: " . $plan->features->count());
                $this->line("    Packages: " . $plan->packages->count());
            });

        } catch (\Exception $e) {
            $this->error('❌ Failed to seed plans: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

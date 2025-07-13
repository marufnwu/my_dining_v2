<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SubscriptionTestSeeder;

class SeedSubscriptionTests extends Command
{
    protected $signature = 'seed:subscription-tests';
    protected $description = 'Seed database with subscription test data';

    public function handle()
    {
        $this->info('🌱 Seeding subscription test data...');

        $seeder = new SubscriptionTestSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('✅ Subscription test data seeded successfully!');
        $this->info('');
        $this->info('�� Test Data Summary:');
        $this->info('   • 5 Test Messes (different subscription states)');
        $this->info('   • 3 Plans (Basic, Premium, Enterprise)');
        $this->info('   • 8 Plan Packages (monthly, yearly, trial)');
        $this->info('   • 4 Subscriptions (active, expired, grace period)');
        $this->info('   • 3 Payment Methods');
        $this->info('   • 2 Manual Payments');
        $this->info('   • Feature Usage tracking');
        $this->info('');
        $this->info('🧪 You can now run your subscription tests!');
    }
}

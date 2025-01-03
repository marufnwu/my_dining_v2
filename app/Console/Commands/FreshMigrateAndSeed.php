<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FreshMigrateAndSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fresh-seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrate:fresh and db:seed commands';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('migrate:fresh');
        $this->call('db:seed');

        $this->info('Database has been freshly migrated and seeded.');

        return 0;
    }
}

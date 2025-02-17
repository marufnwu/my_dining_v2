<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call(CountrySeeder::class);
       $this->call(PlanSeeder::class);

       if (app()->environment('local')) {
           $this->call(DemoUserSeeder::class);
       }
    }
}

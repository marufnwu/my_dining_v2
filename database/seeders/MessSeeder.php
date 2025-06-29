<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('messes')->insert([
            [
                'name' => 'Default Mess',
                'status' => 'active',
                'ad_free' => true,
                'all_user_add_meal' => false,
                'fund_add_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mess A',
                'status' => 'inactive',
                'ad_free' => false,
                'all_user_add_meal' => true,
                'fund_add_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mess B',
                'status' => 'active',
                'ad_free' => true,
                'all_user_add_meal' => true,
                'fund_add_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mess C',
                'status' => 'inactive',
                'ad_free' => false,
                'all_user_add_meal' => false,
                'fund_add_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mess D',
                'status' => 'active',
                'ad_free' => true,
                'all_user_add_meal' => false,
                'fund_add_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

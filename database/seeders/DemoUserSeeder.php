<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Maruf Ahmed',
            'email' => 'maruf@email.com',
            'country_id' => 19,
            'phone' => '1778473031',
            'city' => 'Khulna',
            'gender' => 'male',
            'password' => Hash::make('11111111'),
        ]);
    }
}

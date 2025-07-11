<?php

namespace Database\Seeders;

use App\Models\Mess;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $mess = Mess::first();
        $users = User::whereHas('messUsers', function ($query) use ($mess) {
            $query->where('mess_id', $mess->id);
        })->take(10)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found for mess. Skipping notification seeding.');
            return;
        }

        // Create various types of notifications
        foreach ($users as $user) {
            // Regular notifications
            Notification::factory()->count(5)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);

            // Meal request notifications
            Notification::factory()->mealRequest()->count(2)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);

            // Deposit notifications
            Notification::factory()->deposit()->count(1)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);

            // Low balance warnings
            if (rand(1, 100) <= 30) { // 30% chance
                Notification::factory()->lowBalance()->create([
                    'mess_id' => $mess->id,
                    'user_id' => $user->id,
                ]);
            }

            // Unread notifications
            Notification::factory()->unread()->count(3)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);

            // High priority notifications
            Notification::factory()->highPriority()->count(1)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);

            // Actionable notifications
            Notification::factory()->actionable()->count(2)->create([
                'mess_id' => $mess->id,
                'user_id' => $user->id,
            ]);
        }

        // Broadcast notifications
        Notification::factory()->broadcast()->announcement()->count(3)->create([
            'mess_id' => $mess->id,
            'user_id' => null,
        ]);

        // System maintenance notifications
        Notification::factory()->systemMaintenance()->count(1)->create([
            'mess_id' => $mess->id,
            'user_id' => null,
        ]);

        // Scheduled notifications
        Notification::factory()->scheduled()->count(5)->create([
            'mess_id' => $mess->id,
            'user_id' => $users->random()->id,
        ]);

        // Expired notifications
        Notification::factory()->expired()->count(3)->create([
            'mess_id' => $mess->id,
            'user_id' => $users->random()->id,
        ]);

        $this->command->info('Notification seeding completed successfully!');
    }
}

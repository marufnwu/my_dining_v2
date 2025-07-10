<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Manual Bank Transfer',
                'type' => 'manual',
                'enabled' => true,
                'instructions' => "Please transfer the amount to:\nBank: Example Bank\nAccount: 1234567890\nAccount Name: My Dining\n\nAfter transferring, please submit the transaction ID and proof of payment.",
                'display_order' => 1,
            ],
            [
                'name' => 'bKash',
                'type' => 'manual',
                'enabled' => true,
                'instructions' => "Send money to bKash number: 01234567890\nMerchant Account\n\nAfter sending, please submit the Transaction ID from your bKash app.",
                'display_order' => 2,
            ],
            [
                'name' => 'Google Play',
                'type' => 'google_play',
                'enabled' => true,
                'instructions' => null,
                'config' => json_encode([
                    'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME', 'com.example.mydining'),
                    'subscription_id_prefix' => 'sub_',
                ]),
                'display_order' => 3,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}

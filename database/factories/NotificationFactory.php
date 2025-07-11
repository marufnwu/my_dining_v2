<?php

namespace Database\Factories;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Enums\NotificationTemplate;
use App\Models\Mess;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $category = fake()->randomElement(NotificationCategory::cases());
        $priority = fake()->randomElement(NotificationPriority::cases());

        return [
            'mess_id' => Mess::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(10),
            'type' => fake()->randomElement(['meal_request', 'deposit', 'purchase', 'announcement', 'reminder']),
            'category' => $category,
            'priority' => $priority,
            'data' => [
                'amount' => fake()->numberBetween(100, 1000),
                'date' => fake()->date(),
                'reference' => fake()->uuid(),
            ],
            'action_data' => null,
            'is_broadcast' => fake()->boolean(20),
            'is_actionable' => fake()->boolean(30),
            'is_dismissible' => true,
            'icon' => $category->getIcon(),
            'color' => $priority->getColor(),
            'image_url' => fake()->boolean(10) ? fake()->imageUrl() : null,
            'read_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-1 week') : null,
            'expires_at' => fake()->boolean(20) ? fake()->dateTimeBetween('+1 day', '+1 month') : null,
            'scheduled_at' => null,
            'source' => fake()->randomElement(['System', 'Admin', 'Manager']),
            'delivery_channels' => ['fcm', 'database'],
            'fcm_response' => null,
            'is_delivered' => fake()->boolean(90),
            'delivered_at' => fake()->boolean(90) ? fake()->dateTimeBetween('-1 day') : null,
            'retry_count' => 0,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 week'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->randomElement([NotificationPriority::HIGH, NotificationPriority::URGENT]),
        ]);
    }

    public function actionable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_actionable' => true,
            'action_data' => [
                'approve' => 'Approve',
                'reject' => 'Reject',
                'view' => 'View Details',
                'deep_link' => '/dashboard/items/' . fake()->uuid(),
            ],
        ]);
    }

    public function broadcast(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_broadcast' => true,
            'user_id' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
            'is_delivered' => false,
            'delivered_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function mealRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'meal_request_pending',
            'category' => NotificationCategory::MEAL,
            'priority' => NotificationPriority::NORMAL,
            'is_actionable' => true,
            'action_data' => [
                'approve' => 'Approve',
                'reject' => 'Reject',
                'view' => 'View Details',
                'deep_link' => '/meal-requests/' . fake()->uuid(),
            ],
            'data' => [
                'meal_date' => fake()->date(),
                'meal_types' => fake()->randomElements(['breakfast', 'lunch', 'dinner'], 2),
                'requester_name' => fake()->name(),
            ],
        ]);
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'new_deposit',
            'category' => NotificationCategory::DEPOSIT,
            'priority' => NotificationPriority::NORMAL,
            'data' => [
                'amount' => fake()->numberBetween(500, 2000),
                'depositor_name' => fake()->name(),
                'deposit_method' => fake()->randomElement(['cash', 'bank_transfer', 'mobile_banking']),
            ],
        ]);
    }

    public function lowBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'low_balance_warning',
            'category' => NotificationCategory::ALERT,
            'priority' => NotificationPriority::HIGH,
            'is_actionable' => true,
            'action_data' => [
                'add_deposit' => 'Add Deposit',
                'view_balance' => 'View Balance',
                'deep_link' => '/deposits/create',
            ],
            'data' => [
                'current_balance' => fake()->numberBetween(50, 200),
                'threshold' => 300,
            ],
        ]);
    }

    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom_announcement',
            'category' => NotificationCategory::ANNOUNCEMENT,
            'priority' => NotificationPriority::NORMAL,
            'is_broadcast' => true,
            'data' => [
                'announcement_type' => fake()->randomElement(['general', 'urgent', 'celebration']),
                'author' => fake()->name(),
            ],
        ]);
    }

    public function systemMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system_maintenance',
            'category' => NotificationCategory::SYSTEM,
            'priority' => NotificationPriority::HIGH,
            'is_broadcast' => true,
            'is_dismissible' => false,
            'expires_at' => fake()->dateTimeBetween('+2 hours', '+6 hours'),
            'data' => [
                'maintenance_start' => fake()->dateTimeBetween('+1 hour', '+2 hours'),
                'maintenance_end' => fake()->dateTimeBetween('+3 hours', '+5 hours'),
                'affected_services' => ['API', 'Mobile App'],
            ],
        ]);
    }
}

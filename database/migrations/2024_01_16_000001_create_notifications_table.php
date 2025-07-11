<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('body');
            $table->string('type'); // meal_add, balance_add, purchase, system, etc.
            $table->string('category')->default('system'); // meal, deposit, purchase, etc.
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->json('data')->nullable(); // Additional data specific to notification type
            $table->json('action_data')->nullable(); // Action buttons and deep linking data
            $table->boolean('is_broadcast')->default(false); // Whether sent to all mess members
            $table->boolean('is_actionable')->default(false); // Whether notification has actions
            $table->boolean('is_dismissible')->default(true); // Whether notification can be dismissed
            $table->string('icon')->nullable(); // Icon for the notification
            $table->string('color')->nullable(); // Color theme for the notification
            $table->string('image_url')->nullable(); // Optional image for rich notifications
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For time-sensitive notifications
            $table->timestamp('scheduled_at')->nullable(); // For scheduled notifications
            $table->string('source')->nullable(); // Source of the notification (system, user, etc.)
            $table->json('delivery_channels')->nullable(); // FCM, email, SMS, etc.
            $table->json('fcm_response')->nullable(); // FCM delivery response for debugging
            $table->boolean('is_delivered')->default(false); // Whether FCM delivery was successful
            $table->timestamp('delivered_at')->nullable(); // When FCM delivery was successful
            $table->unsignedInteger('retry_count')->default(0); // Number of retry attempts

            // Enhanced fields for better notification management
            $table->string('template')->nullable(); // Template identifier for consistent messaging
            $table->json('template_data')->nullable(); // Data to populate template variables
            $table->string('group_key')->nullable(); // For grouping related notifications
            $table->boolean('is_silent')->default(false); // For silent notifications (data only)
            $table->string('sound')->nullable(); // Custom notification sound
            $table->string('badge_count')->nullable(); // App badge count
            $table->json('custom_headers')->nullable(); // Custom FCM headers
            $table->string('click_action')->nullable(); // Action when notification is clicked
            $table->string('tag')->nullable(); // For replacing/updating notifications
            $table->boolean('is_sticky')->default(false); // Whether notification persists until dismissed
            $table->timestamp('clicked_at')->nullable(); // When notification was clicked
            $table->timestamp('dismissed_at')->nullable(); // When notification was dismissed
            $table->json('interaction_data')->nullable(); // Track user interactions
            $table->string('locale')->nullable(); // Language/locale for the notification
            $table->foreignId('parent_notification_id')->nullable()->constrained('notifications')->onDelete('set null'); // For threaded notifications
            $table->unsignedInteger('thread_count')->default(0); // Number of replies in thread

            $table->timestamps();

            // Indexes for better performance
            $table->index(['mess_id', 'user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['category', 'priority', 'created_at']);
            $table->index(['is_broadcast', 'mess_id']);
            $table->index(['read_at', 'user_id']);
            $table->index(['expires_at']);
            $table->index(['scheduled_at']);
            $table->index(['template', 'mess_id']);
            $table->index(['group_key']);
            $table->index(['tag', 'user_id']);
            $table->index(['parent_notification_id']);
            $table->index(['is_delivered', 'retry_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

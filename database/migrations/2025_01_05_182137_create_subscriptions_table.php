<?php

use App\Models\Mess;
use App\Models\Plan;
use App\Models\PlanPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->foreignIdFor(Plan::class);
            $table->foreignIdFor(PlanPackage::class);

            // Timing fields
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->integer('admin_grace_period_days')->default(0);

            // Status fields
            $table->string('status')->default('pending');
            $table->boolean('is_canceled')->default(false);
            $table->timestamp('canceled_at')->nullable();

            // Payment fields
            $table->string('payment_method')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->decimal('total_spent', 10, 2)->default(0);

            // Transaction tracking
            $table->string('last_order_id')->nullable();
            $table->string('last_transaction_id')->nullable();
            $table->string('invoice_reference')->nullable();

            // Google Play specific fields
            $table->string('google_play_token')->nullable();
            $table->string('google_play_subscription_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['mess_id', 'status']);
            $table->index('google_play_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

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
            $table->foreignId('mess_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('plan_package_id')->constrained('plan_packages');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();
            $table->boolean('is_canceled')->default(false);
            $table->timestamp('canceled_at')->nullable();

            $table->unsignedBigInteger('last_order_id')->nullable();
            $table->unsignedBigInteger('last_transaction_id')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('billing_cycle')->default('monthly');
            $table->timestamp('next_billing_date')->nullable();
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->string('invoice_reference')->nullable();

            
            $table->timestamps();
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

<?php

use App\Models\Mess;
use App\Models\Plan;
use App\Models\PlanPackage;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->foreignIdFor(Subscription::class);
            $table->foreignIdFor(Plan::class);
            $table->foreignIdFor(PlanPackage::class);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('provider_order_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index('provider_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};

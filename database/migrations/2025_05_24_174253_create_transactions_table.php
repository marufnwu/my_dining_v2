<?php

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('mess_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('subscription_orders')->onDelete('set null');
            $table->string('transaction_reference')->unique();
            $table->string('payment_method');
            $table->string('payment_provider')->nullable();
            $table->string('payment_provider_reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

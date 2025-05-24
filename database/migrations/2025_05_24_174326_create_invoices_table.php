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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('mess_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('subscription_orders')->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->text('billing_address')->nullable();
            $table->string('billing_email')->nullable();
            $table->date('due_date');
            $table->date('issued_date');
            $table->date('paid_date')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

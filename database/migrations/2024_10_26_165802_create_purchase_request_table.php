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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('mess_user_id');
            $table->foreignId('mess_id');
            $table->foreignId('month_id');  // Added month_id that was referenced in the model
            $table->string('type', 10)->nullable();
            $table->integer('purchase_type')->default(1);
            $table->float('price');
            $table->text('product')->nullable();
            $table->longText('product_json')->nullable();
            $table->boolean('deposit_request')->default(false);
            $table->integer('status')->default(0);  // Default to pending (0)
            $table->string('comment')->nullable();
            $table->timestamps();  // Added timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request');
    }
};

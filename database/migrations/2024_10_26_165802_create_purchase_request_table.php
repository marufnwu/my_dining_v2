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
            $table->integer('user_id');
            $table->integer('mess_id');
            $table->string('type', 10);
            $table->integer('purchase_type')->default(1);
            $table->float('price');
            $table->text('product')->nullable();
            $table->longText('product_json')->nullable();
            $table->boolean('deposit_request');
            $table->integer('status');
            $table->string('comment')->nullable();
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

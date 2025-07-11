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
            $table->json('data')->nullable(); // Additional data specific to notification type
            $table->boolean('is_broadcast')->default(false); // Whether sent to all mess members
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['mess_id', 'user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

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
        Schema::create('meal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mess_id')->constrained()->onDelete('cascade');
            $table->foreignId('month_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('breakfast', 8, 2)->default(0);
            $table->decimal('lunch', 8, 2)->default(0);
            $table->decimal('dinner', 8, 2)->default(0);
            $table->tinyInteger('status')->default(0); // 0: pending, 1: approved, 2: rejected, 3: cancelled
            $table->text('comment')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('mess_users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['mess_user_id', 'date']);
            $table->index(['mess_id', 'status']);
            $table->index(['month_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_requests');
    }
};

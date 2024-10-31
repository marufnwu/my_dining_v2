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
        Schema::create('initiate_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('month_id');
            $table->bigInteger('user_id');
            $table->bigInteger('mess_id');
            $table->year('year');
            $table->integer('month');
            $table->boolean('active')->default(1);  // Storing act   ive as a boolean for clarity
            $table->timestamps();  // Adds created_at and updated_at fields
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiate_user');
    }
};

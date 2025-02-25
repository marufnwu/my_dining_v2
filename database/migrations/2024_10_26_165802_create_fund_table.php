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
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('month_id')->default(0);  // Added month_id with a default value of 0
            $table->timestamp('date');  // Includes both date and time
            $table->bigInteger('mess_id');
            $table->float('amount');
            $table->string('comment');
            $table->bigInteger('action_user_id');
            $table->timestamps();  // Adds created_at and updated_at fields
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund');
    }
};

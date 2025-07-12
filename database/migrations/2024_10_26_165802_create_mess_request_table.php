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
        Schema::create('mess_requests', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->integer('user_id');
            $table->integer('old_mess_user_id')->nullable();
            $table->integer('new_mess_user_id')->nullable();
            $table->timestamp('request_date');
            $table->timestamp('accept_date')->nullable();
            $table->integer('old_mess_id')->nullable();
            $table->integer('new_mess_id');
            $table->integer('accept_by')->nullable();
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess_request');
    }
};

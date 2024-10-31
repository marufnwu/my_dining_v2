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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_name', 20)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('country', 6)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('gender', 20);
            $table->string('city', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->integer('acc_type');
            $table->integer('mess_id');
            $table->boolean('active')->default(true);
            $table->timestamp('join_date')->nullable();
            $table->timestamp('leave_date')->nullable();
            $table->text('photo_url')->nullable();
            $table->text('fcm_token')->nullable();
            $table->integer('version')->default(0);
            $table->timestamp('last_active')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

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
        Schema::create('messes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('super_user_id');
            $table->integer('status');
            $table->integer('ad_free')->default(0);
            $table->integer('all_user_add_meal')->default(0);
            $table->integer('fund')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess');
    }
};

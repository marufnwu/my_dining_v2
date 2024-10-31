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
        Schema::create('other_costs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->float('price');
            $table->string('product');
            $table->integer('user_id');
            $table->integer('mess_id')->default(0);
            $table->integer('manager_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_cost');
    }
};

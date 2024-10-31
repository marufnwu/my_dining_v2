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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('mess_id');
            $table->integer('month');
            $table->integer('year');
            $table->dateTime('creation_date')->useCurrent();
            $table->string('pdf');
            $table->string('type');
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report');
    }
};

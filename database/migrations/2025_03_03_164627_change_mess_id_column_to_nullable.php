<?php

use App\Models\Mess;
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
        Schema::table('mess_users', function (Blueprint $table) {
            $table->foreignIdFor(Mess::class)->nullable()->change();
        });
        Schema::table('meals', function (Blueprint $table) {
            $table->foreignIdFor(Mess::class)->nullable()->change();
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignIdFor(Mess::class)->nullable()->change();
        });
        Schema::table('deposits', function (Blueprint $table) {
            $table->foreignIdFor(Mess::class)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mess_users', function (Blueprint $table) {
            //
        });
    }
};

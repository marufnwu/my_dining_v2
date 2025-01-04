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
        Schema::create('mess_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->string('role', 50);
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess_roles');
    }
};

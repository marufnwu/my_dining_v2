<?php

use App\Enums\MonthType;
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
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->string("name", 20)->nullable();
            $table->enum("type", MonthType::values());
            $table->timestamp("start_at")->nullable();
            $table->timestamp("end_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('months');
    }
};

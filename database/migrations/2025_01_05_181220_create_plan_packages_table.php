<?php

use App\Models\Plan;
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
        Schema::create('plan_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Plan::class);
            $table->boolean("is_trial")->default(false);
            $table->boolean("is_free")->default(false);
            $table->integer('duration')->comment('Duration in days');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('default_grace_period_days')->default(3)->comment('Default grace period in days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_packages');
    }
};

<?php

use App\Models\PlanFeature;
use App\Models\Subscription;
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
        Schema::create('feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Subscription::class);
            $table->foreignIdFor(PlanFeature::class);
            $table->integer('used')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_usages');
    }
};

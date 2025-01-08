<?php

use App\Models\Mess;
use App\Models\Plan;
use App\Models\PlanPackage;
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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->foreignIdFor(Plan::class);
            $table->foreignIdFor(PlanPackage::class);
            $table->unsignedBigInteger("order_id")->nullable();
            $table->integer("duration")->comment('Duration in days');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plan_features', function (Blueprint $table) {
            // Add category column if it doesn't exist
            if (!Schema::hasColumn('plan_features', 'category')) {
                $table->string('category')->default('general')->after('reset_period');
            }
        });
    }

    public function down()
    {
        Schema::table('plan_features', function (Blueprint $table) {
            if (Schema::hasColumn('plan_features', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};

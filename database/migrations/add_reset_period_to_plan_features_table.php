<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plan_features', function (Blueprint $table) {
            $table->string('reset_period')->default('monthly')->after('usage_limit');
        });
    }

    public function down()
    {
        Schema::table('plan_features', function (Blueprint $table) {
            $table->dropColumn('reset_period');
        });
    }
};

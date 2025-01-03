<?php

use App\Models\MessRole;
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
        Schema::create('mess_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MessRole::class);
            $table->string("permission", 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess_role_permissions');
    }
};

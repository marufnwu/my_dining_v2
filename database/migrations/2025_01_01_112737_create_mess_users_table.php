<?php

use App\Enums\MessUserRole;
use App\Enums\MessUserStatus;
use App\Models\Mess;
use App\Models\User;
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
        Schema::create('mess_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Mess::class);
            $table->foreignIdFor(User::class);
            $table->string('role')->default(MessUserRole::Member->value);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('status')->default(MessUserStatus::Active->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess_users');
    }
};

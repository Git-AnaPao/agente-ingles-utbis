<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('email_verified_at');
            $table->integer('current_streak')->default(0)->after('last_activity_at');
            $table->integer('longest_streak')->default(0)->after('current_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'current_streak', 'longest_streak']);
        });
    }
};
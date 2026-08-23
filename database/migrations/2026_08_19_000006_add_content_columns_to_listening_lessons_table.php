<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->text('reading_text')->nullable()->after('description');
            $table->text('listening_script')->nullable()->after('reading_text');
            $table->text('speaking_text')->nullable()->after('listening_script');
        });
    }

    public function down(): void
    {
        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->dropColumn(['reading_text', 'listening_script', 'speaking_text']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placement_tests', function (Blueprint $table) {
            $table->unsignedSmallInteger('correct_answers')->default(0)->after('score');
            $table->unsignedSmallInteger('total_questions')->default(0)->after('correct_answers');
            $table->text('level_breakdown')->nullable()->after('total_questions');
        });
    }

    public function down(): void
    {
        Schema::table('placement_tests', function (Blueprint $table) {
            $table->dropColumn(['correct_answers', 'total_questions', 'level_breakdown']);
        });
    }
};

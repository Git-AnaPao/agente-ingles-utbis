<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->unique(['student_id', 'lesson_id'], 'unique_progress_student_lesson');
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique('unique_progress');
        });
    }

    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'student_cefr_level', 'student_skill_type'],
                'unique_progress'
            );
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique('unique_progress_student_lesson');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pedagogical "lesson" the student experiences (e.g. "#1 Presentaciones
     * y Datos Personales") is a `listening_lessons` row, not a `lessons` row
     * (a `lessons` row is really an import batch/unit that groups ~16 of
     * them). Progress needs to be tracked at that finer grain so lessons can
     * be unlocked one at a time within a unit.
     */
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table): void {
            $table->dropForeign(['lesson_id']);
            $table->uuid('lesson_id')->nullable()->change();
            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons')
                ->cascadeOnDelete();

            $table->uuid('listening_lesson_id')->nullable()->after('lesson_id');
            $table->foreign('listening_lesson_id')
                ->references('listening_lesson_id')
                ->on('listening_lessons')
                ->cascadeOnDelete();
        });

        Schema::table('student_progress', function (Blueprint $table): void {
            $table->unique(
                ['student_id', 'listening_lesson_id', 'student_skill_type'],
                'student_progress_listening_lesson_skill_unique',
            );
        });

        // Guards future imports from creating two lessons at the same
        // position within a CEFR level, which would break sequential unlock.
        Schema::table('listening_lessons', function (Blueprint $table): void {
            $table->unique(['cefr_level', 'sort_order'], 'listening_lessons_cefr_sort_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('listening_lessons', function (Blueprint $table): void {
            $table->dropUnique('listening_lessons_cefr_sort_order_unique');
        });

        Schema::table('student_progress', function (Blueprint $table): void {
            $table->dropUnique('student_progress_listening_lesson_skill_unique');
            $table->dropForeign(['listening_lesson_id']);
            $table->dropColumn('listening_lesson_id');

            $table->dropForeign(['lesson_id']);
            $table->uuid('lesson_id')->nullable(false)->change();
            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons');
        });
    }
};

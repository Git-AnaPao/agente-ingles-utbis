<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->index('student_id', 'student_progress_student_id_index');
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique('unique_progress');
            $table->unique(
                ['student_id', 'lesson_id', 'student_skill_type'],
                'student_progress_student_lesson_skill_unique',
            );
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropIndex('student_progress_student_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropUnique('student_progress_student_lesson_skill_unique');
        });

        DB::table('student_progress')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (object $progress): string => $progress->student_id.'|'.$progress->lesson_id)
            ->each(function ($rows): void {
                $duplicateIds = $rows->skip(1)->pluck('student_progress_id');
                if ($duplicateIds->isNotEmpty()) {
                    DB::table('student_progress')->whereIn('student_progress_id', $duplicateIds)->delete();
                }
            });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->unique(['student_id', 'lesson_id'], 'unique_progress');
        });
    }
};

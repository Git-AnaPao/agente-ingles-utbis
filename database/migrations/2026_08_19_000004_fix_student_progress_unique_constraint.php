<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            // 1. Drop the foreign key only if it exists
            $foreignKeys = collect(
                DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'student_progress'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND CONSTRAINT_NAME = 'student_progress_student_id_foreign'")
            );
            if ($foreignKeys->isNotEmpty()) {
                $table->dropForeign(['student_id']);
            }

            // 2. Safely drop the unique index if it exists
            $indexes = collect(
                DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'student_progress'
                    AND INDEX_NAME = 'unique_progress'")
            );
            if ($indexes->isNotEmpty()) {
                $table->dropUnique('unique_progress');
            }

            // 3. Add the new unique index
            $table->unique(['student_id', 'lesson_id'], 'unique_progress');

            // 4. Re-add the foreign key with the correct column name
            $table->foreign('student_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique('unique_progress');
            $table->unique(
                ['student_id', 'student_cefr_level', 'student_skill_type'],
                'unique_progress'
            );
            $table->foreign('student_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }
};

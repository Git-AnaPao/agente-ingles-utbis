<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            // 1. Drop the foreign key first
            $table->dropForeign(['student_id']);
            
            // 2. Safely drop the unique index
            $table->dropUnique('unique_progress');
            
            // 3. Add the new unique index
            $table->unique(['student_id', 'lesson_id'], 'unique_progress');
            
            // 4. Re-add the foreign key
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            // 1. Drop the foreign key again
            $table->dropForeign(['student_id']);

            // 2. Revert the unique index
            $table->dropUnique('unique_progress');
            $table->unique(
                ['student_id', 'student_cefr_level', 'student_skill_type'],
                'unique_progress'
            );

            // 3. Re-add the foreign key
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

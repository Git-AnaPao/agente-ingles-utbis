<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_logs', function (Blueprint $table) {
            $table->string('attempt_skill_type', 20)->nullable()->after('lesson_id');
            $table->uuid('questionnaire_id')->nullable()->after('attempt_skill_type');
            $table->uuid('listening_lesson_id')->nullable()->after('questionnaire_id');

            $table->foreign('questionnaire_id')
                ->references('questionnaire_id')
                ->on('questionnaires')
                ->nullOnDelete();
            $table->foreign('listening_lesson_id')
                ->references('listening_lesson_id')
                ->on('listening_lessons')
                ->nullOnDelete();
            $table->index(
                ['user_id', 'lesson_id', 'attempt_skill_type', 'passed'],
                'attempt_logs_progress_evidence_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('attempt_logs', function (Blueprint $table) {
            $table->dropIndex('attempt_logs_progress_evidence_index');
            $table->dropForeign(['questionnaire_id']);
            $table->dropForeign(['listening_lesson_id']);
            $table->dropColumn([
                'attempt_skill_type',
                'questionnaire_id',
                'listening_lesson_id',
            ]);
        });
    }
};

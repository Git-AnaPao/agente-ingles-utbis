<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_responses', function (Blueprint $table) {
            $table->uuid('response_id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_id');
            $table->text('student_answer_text')
                ->comment('Transcripcion de voz o respuesta escrita del alumno');
            $table->boolean('is_correct')->nullable()
                ->comment('null mientras la IA no haya calificado');
            $table->text('ai_question_feedback')->nullable()
                ->comment('Retroalimentacion individual por pregunta de la IA');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('attempt_id')
                ->references('attempt_id')
                ->on('attempt_logs')
                ->onDelete('cascade');

            $table->foreign('question_id')
                ->references('question_id')
                ->on('questions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_responses');
    }
};

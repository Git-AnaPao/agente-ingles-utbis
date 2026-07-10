<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('question_id')->primary();
            $table->uuid('questionnaire_id');
            $table->enum('question_type', [
                'multiple_choice',
                'fill_blank',
                'speaking',
                'listening'
            ]);
            $table->enum('question_skill_type', [
                'reading',
                'listening',
                'speaking',
                'writing',
            ]);
            $table->text('question_text');
            $table->text('correct_answer')->nullable()
                ->comment('null para preguntas de speaking evaluadas por IA');
            $table->timestamps();

            $table->foreign('questionnaire_id')
                ->references('questionnaire_id')
                ->on('questionnaires')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire', function (Blueprint $table) {
            $table->uuid('questionnaire_id')->primary();
            $table->uuid('resource_id');
            $table->enum('question_type', ['multiple_choice', 'fill_blank']);
            $table->text('question_text');
            $table->text('correct_answer');
            $table->integer('question_order')->default(1);
            $table->timestamps();

            $table->foreign('resource_id')
                ->references('resource_id')
                ->on('resources')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire');
    }
};

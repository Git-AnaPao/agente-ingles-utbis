<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_lessons', function (Blueprint $table) {
            $table->uuid('listening_lesson_id')->primary();
            $table->uuid('lesson_id')->nullable();
            $table->enum('cefr_level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('questions_data')->nullable(); // Preguntas del Excel
            $table->json('answers_data')->nullable(); // Respuestas del Excel
            $table->string('audio_drive_file_id')->nullable(); // ID del archivo de audio en Drive
            $table->string('audio_drive_url')->nullable(); // URL temporal del audio
            $table->string('audio_local_path')->nullable(); // Ruta local si se descarga
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons')
                ->onDelete('set null');

            $table->index('cefr_level');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_lessons');
    }
};

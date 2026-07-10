<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('lesson_id')->primary();
            $table->enum('lesson_cefr_level', ['A1','A2','B1','B2','C1','C2']);
            $table->integer('lesson_sub_level');
            $table->json('lesson_prompt_payload');

            $table->unique(
                ['lesson_cefr_level', 'lesson_sub_level'],
                'unique_lesson'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};

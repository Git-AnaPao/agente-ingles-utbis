<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('option_id')->primary();
            $table->uuid('question_id');
            $table->string('option_text', 500);
            $table->boolean('is_correct')->default(false);

            $table->foreign('question_id')
                ->references('question_id')
                ->on('questions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};

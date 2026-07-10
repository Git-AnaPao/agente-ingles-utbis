<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('resource_id')->primary();
            $table->uuid('questionnaire_id');
            $table->enum('resource_type', ['audio', 'text', 'image']);
            $table->string('resource_url', 500);
            $table->string('resource_title', 255)->nullable();
            $table->text('resource_transcript')->nullable()
                ->comment('Transcripcion del audio para speaking');
            $table->timestamps();

            $table->foreign('questionnaire_id')
                ->references('questionnaire_id')
                ->on('questionnaires')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};

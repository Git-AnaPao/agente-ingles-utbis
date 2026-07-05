<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_options', function (Blueprint $table) {
            $table->uuid('option_id')->primary();
            $table->uuid('questionnaire_id');
            $table->string('option_text', 500);
            $table->boolean('is_correct')->default(false);
            $table->integer('option_order')->default(1);

            $table->foreign('questionnaire_id')
                ->references('questionnaire_id')
                ->on('questionnaire')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_options');
    }
};

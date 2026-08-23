<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->uuid('lesson_id')->nullable()->change();
            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons')
                ->onDelete('cascade');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('question_order')->default(0)->after('question_skill_type');
            $table->string('question_passage', 50)->nullable()->after('question_order');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->unsignedInteger('option_order')->default(0)->after('is_correct');
        });
    }

    public function down(): void
    {
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropColumn('option_order');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_passage', 'question_order']);
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->uuid('lesson_id')->nullable(false)->change();
            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons')
                ->onDelete('cascade');
        });
    }
};
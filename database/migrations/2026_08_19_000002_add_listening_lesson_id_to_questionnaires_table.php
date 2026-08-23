<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->uuid('listening_lesson_id')->nullable()->unique()->after('lesson_id');

            $table->foreign('listening_lesson_id')
                ->references('listening_lesson_id')
                ->on('listening_lessons')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropForeign(['listening_lesson_id']);
            $table->dropColumn('listening_lesson_id');
        });
    }
};

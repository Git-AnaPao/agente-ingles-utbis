<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->uuid('student_progress_id')->primary();
            $table->uuid('student_id');
            $table->uuid('placement_test_id')->nullable();
            $table->uuid('lesson_id');
            $table->enum('student_cefr_level', ['A1','A2','B1','B2','C1','C2']);
            $table->integer('student_sub_level')->default(1);
            $table->enum('student_skill_type', ['listening','speaking','writing','reading']);
            $table->timestamps();

            $table->foreign('student_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('placement_test_id')
                ->references('placement_test_id')
                ->on('placement_tests')
                ->nullOnDelete();

            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons');

            $table->unique(
                ['student_id', 'student_cefr_level', 'student_skill_type'],
                'unique_progress'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};

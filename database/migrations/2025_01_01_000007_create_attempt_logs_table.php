<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_logs', function (Blueprint $table) {
            $table->uuid('attempt_id')->primary();
            $table->uuid('user_id');
            $table->uuid('lesson_id');
            $table->decimal('attempt_score', 5, 2);
            $table->text('ai_feedback')->nullable();
            $table->boolean('passed');
            $table->timestamp('attempted_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('lesson_id')
                ->references('lesson_id')
                ->on('lessons');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_logs');
    }
};

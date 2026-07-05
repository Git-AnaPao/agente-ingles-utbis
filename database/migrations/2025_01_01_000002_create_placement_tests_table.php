<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_tests', function (Blueprint $table) {
            $table->uuid('placement_test_id')->primary();
            $table->uuid('student_id');
            $table->enum('result_level', ['A1','A2','B1','B2','C1','C2']);
            $table->decimal('score', 5, 2);
            $table->timestamp('taken_at')->useCurrent();

            $table->foreign('student_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_tests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->integer('sub_level')->after('cefr_level')->default(1);
            $table->index(['cefr_level', 'sub_level']);
        });
    }

    public function down(): void
    {
        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->dropIndex(['cefr_level', 'sub_level']);
            $table->dropColumn('sub_level');
        });
    }
};

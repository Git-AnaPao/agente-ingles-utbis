<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->mediumText('reading_text')->nullable()->change();
            $table->mediumText('listening_script')->nullable()->change();
            $table->mediumText('speaking_text')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('listening_lessons', function (Blueprint $table) {
            $table->text('reading_text')->nullable()->change();
            $table->text('listening_script')->nullable()->change();
            $table->text('speaking_text')->nullable()->change();
        });
    }
};

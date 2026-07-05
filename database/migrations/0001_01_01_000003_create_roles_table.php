<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('role_id')->primary();
            $table->string('role_name', 255)->unique();
            $table->string('role_description', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

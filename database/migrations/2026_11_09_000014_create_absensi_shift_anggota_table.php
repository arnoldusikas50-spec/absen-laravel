<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260909000002_CreateShiftTables.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_shift_anggota', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('shift_id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('hari')->default(0)->comment('0=semua hari, 1..7=ISO weekday');
            $table->dateTime('created_at')->nullable();
            $table->unique(['user_id', 'hari', 'shift_id']);
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_shift_anggota');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260110010000_AddHolidayTables.php
 *  - 20260617000002_AddAttendancePerformanceIndexes.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_hari_libur', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->date('tanggal');
            $table->string('keterangan', 255);
            $table->dateTime('created_at')->nullable();
            $table->index('tanggal', 'idx_libur_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_hari_libur');
    }
};

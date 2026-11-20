<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 2026-01-08-000001_CreateAttendanceTables.php
 *  - 2026-09-29-000003_AddIsUtamaToAbsensiLokasi.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_lokasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama_lokasi', 100);
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->integer('radius_meter')->nullable()->default(50);
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->boolean('is_utama')->nullable()->default(0)->comment('1 = lokasi utama dipakai untuk absen manual admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_lokasi');
    }
};

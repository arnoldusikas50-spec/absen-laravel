<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 2026-08-02-000001_CreateDapodikTables.php
 *  - 2026-09-11-082300_FixDapodikLogRuangEnum.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dapodik_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->enum('entitas', ['all', 'sekolah', 'gtk', 'siswa', 'rombel', 'mapel', 'pembelajaran', 'nilai', 'ruang'])->default('all');
            $table->enum('metode', ['api', 'offline_json', 'offline_excel'])->default('api');
            $table->enum('status', ['sukses', 'gagal', 'parsial'])->default('sukses');
            $table->integer('total_diperoses')->default(0);
            $table->integer('total_ditambah')->default(0);
            $table->integer('total_diperbarui')->default(0);
            $table->text('keterangan')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dapodik_log');
    }
};

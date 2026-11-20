<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260614000002_CreateAbsensiQrTable.php
 *  - 20260909000003_AddQrTotpColumns.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_qr', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama_acara', 255);
            $table->string('token', 64);
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('lokasi', 255)->nullable();
            $table->boolean('active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('secret', 64)->nullable();
            $table->integer('interval_detik')->default(60);
            $table->string('mode', 16)->default('rotating');
            $table->unique('token');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_qr');
    }
};

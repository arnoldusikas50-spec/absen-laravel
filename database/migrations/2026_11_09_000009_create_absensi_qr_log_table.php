<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_qr_log`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260614000003_CreateAbsensiQrLogTable.php
 *  - 20260909000001_AddPresensiUniqueConstraints.php
 *  - 20261101000001_AddAbsensiQrLogJenis.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_qr_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('qr_id');
            $table->integer('user_id');
            $table->integer('id_guru')->nullable();
            $table->dateTime('scanned_at');
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('jenis', 10)->default('masuk');
            $table->index('qr_id');
            $table->index('user_id');
            $table->unique(['qr_id', 'user_id', 'jenis'], 'uq_qr_user_jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_qr_log');
    }
};

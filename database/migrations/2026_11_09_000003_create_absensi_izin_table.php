<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_izin`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-08-000001_CreateAttendanceTables.php
 *  - 20260110000000_UpdateAbsensiTables.php
 *  - 20260617000001_FixAbsensiIzinJenisEnum.php
 *  - 20260617000002_AddAttendancePerformanceIndexes.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 * Skema disesuaikan dengan database produksi (dump absen.sql, 9 Nov 2026) — koreksi atas port awal dari migrasi CI4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_izin', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('user_id');
            $table->unsignedInteger('id_guru')->nullable()->default(0);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('jenis', 20)->default('ijin');
            $table->string('kategori_cuti', 30)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('bukti', 200)->nullable();
            $table->text('verifikasi_catatan')->nullable();
            $table->integer('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->string('status', 20)->nullable()->default('pending');
            $table->integer('approved_by')->nullable();
            $table->string('verifikasi', 255);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['user_id', 'status', 'tgl_mulai'], 'idx_izin_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_izin');
    }
};

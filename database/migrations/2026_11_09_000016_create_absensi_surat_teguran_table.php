<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_surat_teguran`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - RUNTIME:Attendance.php#surat_teguran
 *  - 20260914000001_AddAckToSuratTeguran.php
 *  - 20260915000001_AddJumlahAlphaAndStatusToSuratTeguran.php
 *  - 20261003120000_AddUniqueSuratTeguran.php
 *  - 20261005140000_AddBatalOtomatisToSuratTeguran.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_surat_teguran', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('id_guru');
            $table->integer('bulan');
            $table->integer('tahun');
            $table->integer('level');
            $table->integer('jumlah_alpha')->nullable()->default(0);
            $table->integer('nomor_urut');
            $table->string('status', 20)->default('aktif');
            $table->dateTime('created_at');
            $table->dateTime('ack_at')->nullable();
            $table->string('ack_text', 60)->nullable();
            $table->string('keterangan_batal', 255)->nullable();
            $table->boolean('dibatalkan_otomatis')->nullable()->default(0)->comment('1 = dibatalkan otomatis karena alpha turun, bisa dihidupkan lagi');
            $table->unique(['id_guru', 'bulan', 'tahun', 'level'], 'uniq_surat_teguran_guru_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_surat_teguran');
    }
};

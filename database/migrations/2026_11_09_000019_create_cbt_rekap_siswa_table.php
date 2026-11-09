<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `cbt_rekap_siswa`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-15-155500_AddCbtRekapColumns.php
 *  - 2026-01-15-AddIndexesCbtTables.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_rekap_siswa', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id_rekap_siswa');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_jadwal');
            $table->unsignedInteger('id_bank')->nullable();
            $table->unsignedInteger('id_kelas')->nullable();
            $table->string('nama_siswa', 255)->nullable();
            $table->string('no_peserta', 50)->nullable();
            $table->longText('jawaban')->nullable();
            $table->text('nilai')->nullable();
            $table->text('nilai_input')->nullable();
            $table->dateTime('mulai')->nullable();
            $table->dateTime('selesai')->nullable();
            $table->text('pelanggaran')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_siswa', 'idx_rekapsiswa_siswa');
            $table->index('id_jadwal', 'idx_rekapsiswa_jadwal');
            $table->index('id_kelas', 'idx_id_kelas');
            $table->index(['id_jadwal', 'id_siswa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_rekap_siswa');
    }
};

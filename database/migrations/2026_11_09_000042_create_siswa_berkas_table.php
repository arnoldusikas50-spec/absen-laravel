<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `siswa_berkas`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-032338_CreateSiswaBerkasTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_berkas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_siswa');
            $table->string('file_kk', 255)->nullable();
            $table->string('file_akta', 255)->nullable();
            $table->string('file_ijazah', 255)->nullable();
            $table->string('file_rapor', 255)->nullable();
            $table->string('file_foto', 255)->nullable();
            $table->string('file_ktp_ortu', 255)->nullable();
            $table->string('file_pernyataan', 255)->nullable();
            $table->string('file_kip', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_siswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_berkas');
    }
};

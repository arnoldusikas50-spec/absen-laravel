<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `siswa_prestasi`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-004702_CreateSiswaPrestasiTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_prestasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_siswa');
            $table->string('jenis', 5)->nullable()->comment('1=Sains, 2=Seni, 3=Olahraga, 4=Lain-lain');
            $table->string('tingkat', 5)->nullable()->comment('1=Sekolah, 2=Kecamatan, 3=Kabupaten, 4=Provinsi, 5=Nasional, 6=Internasional');
            $table->string('nama', 200)->nullable()->comment('Nama prestasi/perlombaan');
            $table->year('tahun')->nullable();
            $table->string('penyelenggara', 150)->nullable();
            $table->string('peringkat', 20)->nullable()->comment('Peringkat/juara');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_siswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_prestasi');
    }
};

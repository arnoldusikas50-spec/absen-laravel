<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `siswa_ortu`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-004701_CreateSiswaOrtuTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_ortu', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_siswa');
            $table->string('nama_ayah', 100)->nullable();
            $table->string('nik_ayah', 16)->nullable();
            $table->year('tahun_lahir_ayah')->nullable();
            $table->string('pendidikan_ayah', 5)->nullable();
            $table->string('pekerjaan_ayah', 5)->nullable();
            $table->string('penghasilan_ayah', 5)->nullable();
            $table->text('berkebutuhan_khusus_ayah')->nullable();
            $table->string('nama_ibu', 100)->nullable();
            $table->string('nik_ibu', 16)->nullable();
            $table->year('tahun_lahir_ibu')->nullable();
            $table->string('pendidikan_ibu', 5)->nullable();
            $table->string('pekerjaan_ibu', 5)->nullable();
            $table->string('penghasilan_ibu', 5)->nullable();
            $table->text('berkebutuhan_khusus_ibu')->nullable();
            $table->string('nama_wali', 100)->nullable();
            $table->string('nik_wali', 16)->nullable();
            $table->year('tahun_lahir_wali')->nullable();
            $table->string('pendidikan_wali', 5)->nullable();
            $table->string('pekerjaan_wali', 5)->nullable();
            $table->string('penghasilan_wali', 5)->nullable();
            $table->string('no_telepon_rumah', 20)->nullable();
            $table->string('no_hp_ortu', 20)->nullable();
            $table->string('email_ortu', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_siswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_ortu');
    }
};

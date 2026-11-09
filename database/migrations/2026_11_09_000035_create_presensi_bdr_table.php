<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `presensi_bdr`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20261030000001_AddBdrSupport.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_bdr', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->enum('lingkup', ['SEMUA', 'TINGKAT', 'KELAS'])->default('KELAS');
            $table->integer('id_kelas')->nullable();
            $table->integer('level_id')->nullable();
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->string('judul', 255)->default('Belajar dari rumah');
            $table->text('keterangan')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['tgl_mulai', 'tgl_selesai']);
            $table->index('id_kelas');
            $table->index('level_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_bdr');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `ppdb_setting`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-050000_CreatePpdbSettingTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_setting', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('tahun_ajaran', 20)->nullable();
            $table->date('tanggal_buka')->nullable();
            $table->date('tanggal_tutup')->nullable();
            $table->integer('kuota')->default(200);
            $table->integer('jumlah_kelas')->default(6);
            $table->string('whatsapp', 20)->nullable();
            $table->text('pesan_selamat')->nullable();
            $table->text('pesan_tidak_lulus')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_setting');
    }
};

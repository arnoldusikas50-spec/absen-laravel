<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_surat_panggilan`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20261005120000_CreateSuratPanggilan.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_surat_panggilan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_siswa')->comment('master_siswa.id_siswa');
            $table->tinyInteger('bulan')->comment('1-12');
            $table->unsignedSmallInteger('tahun');
            $table->tinyInteger('level')->comment('Ambang alpha yang memicu surat (3/5/7)');
            $table->unsignedSmallInteger('jumlah_alpha')->default(0)->comment('Snapshot total alpha saat surat terbit');
            $table->unsignedSmallInteger('jumlah_terlambat')->default(0)->comment('Snapshot jumlah keterlambatan saat surat terbit');
            $table->unsignedInteger('nomor_urut')->default(0);
            $table->string('sumber', 12)->default('cron')->comment('cron | manual');
            $table->string('status', 20)->default('aktif')->comment('aktif | dibatalkan');
            $table->string('keterangan_batal', 255)->nullable();
            $table->string('keterangan', 255)->nullable()->comment('Alasan untuk surat manual');
            $table->string('penerima', 150)->nullable()->comment('Snapshot nama orang tua/wali tujuan');
            $table->string('nohp_penerima', 25)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['id_siswa', 'bulan', 'tahun', 'level'], 'uniq_panggilan_siswa_periode');
            $table->index(['bulan', 'tahun']);
            $table->index('nomor_urut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_surat_panggilan');
    }
};

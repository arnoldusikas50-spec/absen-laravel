<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_pengumuman_harian`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260930120000_CreateAbsensiPengumumanHarianTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_pengumuman_harian', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->date('tgl');
            $table->unsignedInteger('user_id');
            $table->string('nama_guru', 191);
            $table->dateTime('jam_masuk')->nullable();
            $table->boolean('dm_terkirim')->default(0)->comment('1 bila DM personal ke guru tersebut berhasil');
            $table->boolean('grup_terkirim')->default(0)->comment('1 bila pengumuman grup berhasil');
            $table->string('catatan', 255)->nullable()->comment('Alasan DM gagal, mis. guru belum menautkan Telegram');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('tgl');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_pengumuman_harian');
    }
};

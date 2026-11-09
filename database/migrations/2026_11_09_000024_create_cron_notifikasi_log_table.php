<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `cron_notifikasi_log`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20261002120000_CreateCronNotifikasiLog.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_notifikasi_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('jenis', 40);
            $table->string('periode', 20);
            $table->boolean('terkirim')->default(0)->comment('1 bila gateway menerima pesan; 0 = masih boleh dicoba lagi');
            $table->integer('percobaan')->default(0)->comment('Berapa kali endpoint ini dipanggil untuk periode ini');
            $table->string('catatan', 255)->nullable()->comment('Alasan kegagalan terakhir');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['jenis', 'periode']);
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_notifikasi_log');
    }
};

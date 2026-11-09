<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `user_notifikasi`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260925000001_CreateUserNotifikasiTable.php
 *  - 20261001120000_AddOpenWAGateway.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 * Skema disesuaikan dengan database produksi (dump absen.sql, 9 Nov 2026) — koreksi atas port awal dari migrasi CI4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifikasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('telegram_chat_id', 50)->nullable();
            $table->string('wa_number', 20)->nullable()->comment('Nomor WhatsApp tujuan notifikasi personal (digit saja)');
            $table->boolean('wa_verified')->nullable()->default(0)->comment('1 bila admin sudah mengonfirmasi nomor ini milik user tersebut');
            $table->text('prefs')->nullable()->comment('JSON preferensi: {"denda":true,"teguran":true,"alpha":true,"kehadiran":true}');
            $table->string('link_token', 64)->nullable();
            $table->dateTime('link_expires')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            // Nama unique di dump (`user_id`) dipakai juga oleh tabel lain;
            // di SQLite nama index harus unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->unique('user_id', $isMysql ? 'user_id' : 'user_notifikasi_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifikasi');
    }
};

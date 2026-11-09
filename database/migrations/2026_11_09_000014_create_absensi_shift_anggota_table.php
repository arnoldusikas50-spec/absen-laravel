<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_shift_anggota`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260909000002_CreateShiftTables.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_shift_anggota', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('shift_id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('hari')->default(0)->comment('0=semua hari, 1..7=ISO weekday');
            $table->dateTime('created_at')->nullable();
            $table->unique(['user_id', 'hari', 'shift_id']);
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_shift_anggota');
    }
};

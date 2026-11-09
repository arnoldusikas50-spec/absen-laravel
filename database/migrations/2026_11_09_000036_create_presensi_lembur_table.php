<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `presensi_lembur`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260614000005_CreatePresensiLemburTable.php
 *  - 20261103120000_AddLemburCatatanAdmin.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_lembur', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('user_id');
            $table->integer('id_guru')->nullable();
            $table->date('tgl');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->decimal('total_jam', 5, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->index('user_id');
            $table->index('tgl');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_lembur');
    }
};

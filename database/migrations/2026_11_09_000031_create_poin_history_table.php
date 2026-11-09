<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `poin_history`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260614000007_CreateRewardTables.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poin_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->integer('poin')->default(0);
            $table->string('tipe', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('ref_id', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index('user_id');
            $table->index('tipe');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poin_history');
    }
};

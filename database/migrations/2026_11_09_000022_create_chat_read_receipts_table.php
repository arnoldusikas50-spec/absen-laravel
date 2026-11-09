<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `chat_read_receipts`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-060000_CreateChatTables.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_read_receipts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('message_id');
            $table->unsignedInteger('user_id');
            $table->enum('user_type', ['admin', 'guru', 'panitia', 'siswa'])->default('guru');
            $table->dateTime('read_at')->nullable();
            $table->index(['message_id', 'user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_read_receipts');
    }
};

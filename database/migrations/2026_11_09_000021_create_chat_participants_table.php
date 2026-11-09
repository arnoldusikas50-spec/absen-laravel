<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `chat_participants`.
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
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('user_id');
            $table->enum('user_type', ['admin', 'guru', 'panitia', 'siswa'])->default('guru');
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->dateTime('last_read_at')->nullable();
            $table->boolean('is_muted')->default(0);
            $table->dateTime('joined_at')->nullable();
            $table->index(['room_id', 'user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};

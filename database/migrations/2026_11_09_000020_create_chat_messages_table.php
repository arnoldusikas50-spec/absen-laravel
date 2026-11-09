<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `chat_messages`.
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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('sender_id')->nullable();
            $table->enum('sender_type', ['admin', 'guru', 'panitia', 'siswa', 'system', 'ai'])->default('guru');
            $table->text('message')->nullable();
            $table->enum('message_type', ['text', 'image', 'file', 'system', 'ai'])->default('text');
            $table->string('attachment', 500)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->unsignedBigInteger('reply_to')->nullable();
            $table->boolean('is_edited')->default(0);
            $table->boolean('is_deleted')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('room_id');
            $table->index('sender_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};

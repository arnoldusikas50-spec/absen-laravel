<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `post_likes`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-13-033000_CreatePostLikesTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_likes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_like', true);
            $table->integer('id_post');
            $table->integer('id_comment')->nullable();
            $table->integer('dari')->default(0);
            $table->boolean('dari_group')->default(1);
            $table->dateTime('created_at');
            $table->index('id_post');
            $table->index('id_comment');
            $table->index(['dari', 'dari_group']);
            $table->unique(['id_post', 'id_comment', 'dari', 'dari_group'], 'unique_like_post');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};

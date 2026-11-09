<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `post` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_post', true);
            $table->integer('dari')->nullable();
            $table->integer('dari_group')->nullable();
            $table->string('kepada', 50)->comment('group');
            $table->longText('text');
            $table->dateTime('tanggal')->useCurrent();
            $table->dateTime('updated')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post');
    }
};

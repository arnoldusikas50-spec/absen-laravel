<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `level_kelas` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_kelas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_level');
            $table->integer('level')->nullable();
            $table->primary('id_level');
            $table->index('id_level', 'index_id_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_kelas');
    }
};

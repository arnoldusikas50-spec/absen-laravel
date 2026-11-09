<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_kelompok_mapel` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_kelompok_mapel', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kel_mapel', true);
            $table->string('kode_kel_mapel', 10)->nullable();
            $table->string('nama_kel_mapel', 100)->nullable();
            $table->string('kategori', 20)->nullable();
            $table->integer('id_parent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kelompok_mapel');
    }
};

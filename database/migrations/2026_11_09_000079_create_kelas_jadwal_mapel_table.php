<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_jadwal_mapel` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_jadwal_mapel', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_jadwal', true);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_kelas')->nullable();
            $table->integer('id_hari');
            $table->integer('jam_ke');
            $table->integer('id_mapel')->nullable();
            $table->string('dari', 10)->nullable();
            $table->string('sampai', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_jadwal_mapel');
    }
};

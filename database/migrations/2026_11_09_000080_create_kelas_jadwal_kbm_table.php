<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_jadwal_kbm` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_jadwal_kbm', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kbm', true);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_kelas')->nullable();
            $table->integer('kbm_jam_pel');
            $table->string('kbm_jam_mulai', 5);
            $table->string('kbm_jam_selesai', 5)->nullable();
            $table->integer('kbm_jml_mapel_hari');
            $table->mediumText('istirahat');
            $table->text('upacara')->nullable();
            $table->integer('libur')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_jadwal_kbm');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_jadwal_materi` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_jadwal_materi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kjm', true);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_materi')->nullable();
            $table->integer('id_mapel')->nullable();
            $table->integer('id_kelas')->nullable();
            $table->string('jadwal_materi', 20);
            $table->integer('jenis')->nullable()->comment('1=materi, 2=tugas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_jadwal_materi');
    }
};

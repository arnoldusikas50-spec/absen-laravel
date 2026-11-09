<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `absensi_jadwal_kerja` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_jadwal_kerja', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('hari');
            $table->time('jam_masuk')->default('07:30:00');
            $table->time('jam_pulang')->default('15:00:00');
            $table->boolean('is_libur')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_jadwal_kerja');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `buku_induk` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_induk', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_siswa');
            $table->string('uid', 255)->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->string('tahun_masuk', 30)->nullable();
            $table->string('tahun_lulus', 30)->nullable();
            $table->string('kelas_awal', 30)->nullable();
            $table->string('kelas_akhir', 30)->nullable();
            $table->primary('id_siswa');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_induk');
    }
};

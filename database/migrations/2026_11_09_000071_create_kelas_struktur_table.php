<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_struktur` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_struktur', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kelas', true);
            $table->integer('ketua')->nullable();
            $table->integer('wakil_ketua')->nullable();
            $table->integer('sekretaris_1')->nullable();
            $table->integer('sekretaris_2')->nullable();
            $table->integer('bendahara_1')->nullable();
            $table->integer('bendahara_2')->nullable();
            $table->integer('sie_ekstrakurikuler')->nullable();
            $table->integer('sie_upacara')->nullable();
            $table->integer('sie_olahraga')->nullable();
            $table->integer('sie_keagamaan')->nullable();
            $table->integer('sie_keamanan')->nullable();
            $table->integer('sie_ketertiban')->nullable();
            $table->integer('sie_kebersihan')->nullable();
            $table->integer('sie_keindahan')->nullable();
            $table->integer('sie_kesehatan')->nullable();
            $table->integer('sie_kekeluargaan')->nullable();
            $table->integer('sie_humas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_struktur');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_ekstra` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_ekstra', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->string('id_kelas_ekstra', 50);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_kelas')->nullable();
            $table->longText('ekstra');
            $table->primary('id_kelas_ekstra');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_ekstra');
    }
};

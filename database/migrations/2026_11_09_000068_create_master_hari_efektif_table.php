<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_hari_efektif` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_hari_efektif', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_hari_efektif', true);
            $table->integer('jml_hari_efektif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_hari_efektif');
    }
};

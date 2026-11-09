<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `jabatan_guru` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan_guru', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->string('id_jabatan_guru', 50);
            $table->integer('id_guru')->nullable();
            $table->integer('id_jabatan');
            $table->integer('id_kelas')->nullable()->default(0);
            $table->longText('mapel_kelas')->nullable();
            $table->longText('ekstra_kelas')->nullable();
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->primary('id_jabatan_guru');
            $table->unique(['id_guru', 'id_tp', 'id_smt'], 'uniq_jabatan_guru_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_guru');
    }
};

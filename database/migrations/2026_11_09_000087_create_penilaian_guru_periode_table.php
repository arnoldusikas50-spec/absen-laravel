<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `penilaian_guru_periode` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_guru_periode', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama', 255);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->decimal('bobot_kehadiran', 5, 2)->default(30.00);
            $table->decimal('bobot_mengajar', 5, 2)->default(40.00);
            $table->decimal('bobot_piket', 5, 2)->default(30.00);
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_guru_periode');
    }
};

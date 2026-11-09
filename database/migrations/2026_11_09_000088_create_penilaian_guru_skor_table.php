<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `penilaian_guru_skor` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_guru_skor', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('id_periode');
            $table->integer('id_guru');
            $table->decimal('skor_kehadiran', 5, 2)->default(0.00);
            $table->decimal('skor_mengajar', 5, 2)->default(0.00);
            $table->decimal('skor_piket', 5, 2)->default(0.00);
            $table->decimal('skor_akhir', 5, 2)->default(0.00);
            $table->string('predikat', 5)->nullable();
            $table->longText('detail')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['id_periode', 'id_guru'], 'id_periode_id_guru');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_guru_skor');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `guru_jadwal_pribadi` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_jadwal_pribadi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_guru');
            $table->boolean('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('id_mapel');
            $table->integer('id_kelas');
            $table->string('ruangan', 50)->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_guru', 'id_tp', 'id_smt', 'hari'], 'id_guru_id_tp_id_smt_hari');
            $table->index(['id_kelas', 'id_tp', 'id_smt', 'hari'], 'id_kelas_id_tp_id_smt_hari');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_jadwal_pribadi');
    }
};

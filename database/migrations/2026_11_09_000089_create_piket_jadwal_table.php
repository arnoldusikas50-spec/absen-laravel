<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `piket_jadwal` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_jadwal', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->string('hari', 20)->comment('Senin, Selasa, etc.');
            $table->integer('id_guru');
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_tp', 'id_smt', 'hari'], 'id_tp_id_smt_hari');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_jadwal');
    }
};

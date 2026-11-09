<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `piket_presensi_guru` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_presensi_guru', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->integer('id_kelas');
            $table->integer('jam_ke');
            $table->integer('id_mapel');
            $table->integer('id_guru');
            $table->string('status', 10)->comment('H, S, I, A');
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->boolean('is_auto')->nullable()->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->unique(['tanggal', 'id_kelas', 'jam_ke', 'id_mapel'], 'uq_ppg_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_presensi_guru');
    }
};

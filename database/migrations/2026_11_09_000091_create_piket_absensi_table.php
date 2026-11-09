<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `piket_absensi` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_absensi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->integer('id_kelas');
            $table->integer('id_siswa');
            $table->string('status', 5)->comment('H, S, I, A, T');
            $table->string('keterangan', 255)->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->unique(['tanggal', 'id_siswa'], 'tanggal_id_siswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_absensi');
    }
};

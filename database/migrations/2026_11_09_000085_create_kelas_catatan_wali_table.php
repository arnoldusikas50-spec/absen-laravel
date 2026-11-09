<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_catatan_wali` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_catatan_wali', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_catatan', true);
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('type')->comment('1=semua siswa, 2=per siswa');
            $table->string('level', 1)->comment('1=saran, 2=teguran, 3=peringatan, 4=sangsi');
            $table->dateTime('tgl')->useCurrent()->useCurrentOnUpdate();
            $table->integer('id_siswa')->nullable();
            $table->integer('id_kelas')->nullable();
            $table->mediumText('text');
            $table->string('readed', 22)->default('0');
            $table->longText('reading')->nullable();
            $table->integer('jml')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_catatan_wali');
    }
};

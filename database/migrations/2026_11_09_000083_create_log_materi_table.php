<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `log_materi` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_materi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_log', true);
            $table->dateTime('log_time')->useCurrent();
            $table->integer('id_siswa')->nullable();
            $table->integer('jam_ke');
            $table->string('id_materi', 50);
            $table->integer('id_mapel')->nullable();
            $table->integer('log_type');
            $table->mediumText('log_desc');
            $table->longText('text')->nullable();
            $table->longText('file')->nullable();
            $table->string('nilai', 3)->nullable();
            $table->longText('catatan')->nullable();
            $table->string('ulang', 1)->default('0');
            $table->mediumText('address');
            $table->mediumText('agent');
            $table->mediumText('device');
            $table->string('finish_time', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_materi');
    }
};

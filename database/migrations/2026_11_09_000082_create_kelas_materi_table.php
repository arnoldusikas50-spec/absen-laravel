<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_materi` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_materi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_materi', true);
            $table->integer('id_tp')->default(1);
            $table->integer('id_smt')->default(1);
            $table->mediumText('kode_materi');
            $table->integer('id_guru')->nullable();
            $table->mediumText('materi_kelas');
            $table->integer('id_mapel')->nullable()->default(0);
            $table->string('kode_mapel', 300)->nullable();
            $table->mediumText('judul_materi');
            $table->longText('isi_materi');
            $table->longText('file')->nullable();
            $table->string('link_file', 255)->nullable();
            $table->dateTime('tgl_mulai')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->dateTime('updated_on')->nullable()->useCurrent();
            $table->integer('status')->nullable();
            $table->string('youtube', 255);
            $table->integer('jenis')->default(1)->comment('1=materi, 2=tugas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_materi');
    }
};

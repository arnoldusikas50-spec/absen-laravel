<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `kelas_siswa` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_siswa', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kelas_siswa');
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_siswa')->nullable();
            $table->integer('id_kelas')->nullable();
            $table->primary('id_kelas_siswa');
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->unique('id_kelas_siswa', 'id_kelas_siswa_idx');
            $table->unique(['id_tp', 'id_smt', 'id_siswa'], 'uq_ks_tp_smt_siswa');
            $table->index('id_siswa', $isMysql ? 'id_siswa_idx' : 'kelas_siswa_id_siswa_idx_index');
            $table->index('id_kelas', $isMysql ? 'Id_kelas' : 'kelas_siswa_Id_kelas_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_siswa');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_jurusan` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_jurusan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_jurusan', true);
            $table->string('nama_jurusan', 30);
            $table->string('kode_jurusan', 10)->nullable();
            $table->longText('mapel_peminatan')->nullable();
            $table->integer('status')->default(1);
            $table->integer('deletable')->default(1);
            $table->string('sync_school_uuid', 50)->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('sync_school_uuid', $isMysql ? 'sync_school_uuid' : 'master_jurusan_sync_school_uuid_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_jurusan');
    }
};

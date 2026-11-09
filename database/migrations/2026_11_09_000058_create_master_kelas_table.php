<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_kelas` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Satu-satunya foreign key di database produksi:
 * level_id -> level_kelas.id_level (constraint key_id_cek).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_kelas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_kelas', true);
            $table->string('dapo_id', 50)->nullable();
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->string('nama_kelas', 30);
            $table->string('kode_kelas', 20)->nullable();
            $table->integer('jurusan_id')->nullable();
            $table->integer('level_id');
            $table->integer('guru_id')->nullable();
            $table->integer('siswa_id')->nullable();
            $table->longText('jumlah_siswa')->nullable();
            $table->string('set_siswa', 1)->nullable()->default('0');
            $table->string('sync_uuid', 36)->nullable();
            $table->enum('sync_status', ['synced', 'pending', 'conflict'])->nullable()->default('pending');
            $table->dateTime('sync_at')->nullable();
            $table->enum('sync_origin', ['pusat', 'lokal'])->nullable()->default('lokal');
            $table->string('sync_school_uuid', 50)->nullable();
            $table->dateTime('deleted_at')->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('level_id', 'index_level_Id');
            $table->index('sync_uuid', $isMysql ? 'idx_sync_uuid' : 'master_kelas_idx_sync_uuid_index');
            $table->index('sync_school_uuid', $isMysql ? 'sync_school_uuid' : 'master_kelas_sync_school_uuid_index');
            $table->index('deleted_at', $isMysql ? 'idx_deleted_at' : 'master_kelas_idx_deleted_at_index');
            $table->foreign('level_id', 'key_id_cek')->references('id_level')->on('level_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kelas');
    }
};

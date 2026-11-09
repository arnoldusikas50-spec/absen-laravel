<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_mapel` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_mapel', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_mapel', true);
            $table->string('dapo_id', 50)->nullable();
            $table->string('nama_mapel', 50);
            $table->string('kode', 20)->nullable();
            $table->string('kelompok', 5)->default('-');
            $table->integer('bobot_p')->default(0);
            $table->integer('bobot_k')->default(0);
            $table->integer('jenjang')->default(0);
            $table->integer('urutan');
            $table->integer('status')->default(1);
            $table->integer('deletable')->default(1);
            $table->integer('urutan_tampil')->nullable();
            $table->string('sync_uuid', 36)->nullable();
            $table->enum('sync_status', ['synced', 'pending', 'conflict'])->nullable()->default('pending');
            $table->dateTime('sync_at')->nullable();
            $table->enum('sync_origin', ['pusat', 'lokal'])->nullable()->default('lokal');
            $table->string('sync_school_uuid', 50)->nullable();
            $table->dateTime('deleted_at')->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('sync_uuid', $isMysql ? 'idx_sync_uuid' : 'master_mapel_idx_sync_uuid_index');
            $table->index('sync_school_uuid', $isMysql ? 'sync_school_uuid' : 'master_mapel_sync_school_uuid_index');
            $table->index('deleted_at', $isMysql ? 'idx_deleted_at' : 'master_mapel_idx_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_mapel');
    }
};

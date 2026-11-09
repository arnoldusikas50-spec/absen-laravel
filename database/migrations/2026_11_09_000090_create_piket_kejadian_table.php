<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `piket_kejadian` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_kejadian', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->string('jenis', 50)->comment('Pelanggaran, Kejadian, Kecelakaan, Lainnya');
            $table->string('tingkat', 20)->nullable()->default('ringan');
            $table->string('pelapor', 255)->nullable();
            $table->integer('terlapor_siswa_id')->nullable();
            $table->string('terlapor_lain', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->text('penanganan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->string('status', 20)->nullable()->default('belum');
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('tanggal', $isMysql ? 'tanggal' : 'piket_kejadian_tanggal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_kejadian');
    }
};

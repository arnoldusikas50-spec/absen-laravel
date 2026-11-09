<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `guru_jurnal` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_jurnal', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id_jurnal');
            $table->integer('id_guru');
            $table->integer('id_jadwal')->nullable();
            $table->integer('id_kelas');
            $table->integer('id_mapel');
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->date('tanggal');
            $table->string('jam_ke', 20)->nullable();
            $table->text('materi_pokok')->nullable();
            $table->text('tujuan_pembelajaran')->nullable();
            $table->string('model_belajar', 100)->nullable();
            $table->text('kegiatan_belajar')->nullable();
            $table->text('profil_pancasila')->nullable()->comment('JSON');
            $table->string('jenis_asesmen', 50)->nullable();
            $table->text('refleksi')->nullable();
            $table->text('media_alat')->nullable();
            $table->text('siswa_absent')->nullable()->comment('JSON array of student IDs');
            $table->text('tindak_lanjut')->nullable();
            $table->text('catatan_khusus')->nullable();
            $table->string('validasi_status', 20)->nullable()->default('PENDING');
            $table->integer('validasi_oleh')->nullable();
            $table->dateTime('validasi_pada')->nullable();
            $table->string('validasi_catatan', 500)->nullable();
            $table->string('dokumentasi', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('id_guru', 'id_guru');
            $table->index('id_kelas', $isMysql ? 'id_kelas' : 'guru_jurnal_id_kelas_index');
            $table->index('tanggal', $isMysql ? 'tanggal' : 'guru_jurnal_tanggal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_jurnal');
    }
};

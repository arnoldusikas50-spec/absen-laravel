<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_siswa` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Primary key komposit (id_siswa, uid, nisn, nis) dengan AUTO_INCREMENT
 * pada id_siswa. SQLite tidak mengizinkan AUTOINCREMENT pada PK komposit,
 * jadi autoIncrement hanya dipakai bila driver-nya MySQL; di SQLite
 * id_siswa ditulis sebagai integer biasa dan PK komposit tetap berlaku.
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_siswa', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->integer('id_siswa')->autoIncrement();
            } else {
                $table->integer('id_siswa');
            }
            $table->string('dapo_id', 50)->nullable();
            $table->string('nisn', 50);
            $table->string('nis', 20);
            $table->string('nama', 50);
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('username', 50);
            $table->text('password')->nullable();
            $table->integer('kelas_awal')->nullable();
            $table->string('tahun_masuk', 30)->nullable();
            $table->string('sekolah_asal', 100)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->string('agama', 10)->nullable();
            $table->string('hp', 15)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('foto', 255)->nullable()->default('siswa.png');
            $table->integer('anak_ke')->nullable();
            $table->string('status_keluarga', 1)->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->integer('kode_pos')->nullable();
            $table->string('nama_ayah', 150)->nullable();
            $table->string('tgl_lahir_ayah', 50)->nullable();
            $table->string('pendidikan_ayah', 50)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('nohp_ayah', 20)->nullable();
            $table->longText('alamat_ayah')->nullable();
            $table->string('nama_ibu', 50)->nullable();
            $table->string('tgl_lahir_ibu', 50)->nullable();
            $table->string('pendidikan_ibu', 50)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('nohp_ibu', 20)->nullable();
            $table->longText('alamat_ibu')->nullable();
            $table->string('nama_wali', 150)->nullable();
            $table->string('tgl_lahir_wali', 50)->nullable();
            $table->string('pendidikan_wali', 50)->nullable();
            $table->string('pekerjaan_wali', 100)->nullable();
            $table->string('nohp_wali', 20)->nullable();
            $table->longText('alamat_wali')->nullable();
            $table->string('nik', 30);
            $table->string('warga_negara', 20);
            $table->string('uid', 255);
            $table->string('sync_uuid', 36)->nullable();
            $table->enum('sync_status', ['synced', 'pending', 'conflict'])->nullable()->default('pending');
            $table->dateTime('sync_at')->nullable();
            $table->enum('sync_origin', ['pusat', 'lokal'])->nullable()->default('lokal');
            $table->string('sync_school_uuid', 50)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->boolean('ppdb_status')->nullable()->default(0);
            $table->integer('poin_disiplin')->default(100);
            $table->primary(['id_siswa', 'uid', 'nisn', 'nis']);
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->unique('id_siswa', $isMysql ? 'Id_siswa_idx' : 'master_siswa_Id_siswa_idx_index');
            $table->unique('uid', 'uid_idx');
            $table->unique('nisn', 'nisn');
            $table->index('sync_uuid', $isMysql ? 'idx_sync_uuid' : 'master_siswa_idx_sync_uuid_index');
            $table->index('sync_school_uuid', $isMysql ? 'sync_school_uuid' : 'master_siswa_sync_school_uuid_index');
            $table->index('deleted_at', $isMysql ? 'idx_deleted_at' : 'master_siswa_idx_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_siswa');
    }
};

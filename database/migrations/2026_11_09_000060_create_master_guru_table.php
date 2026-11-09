<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `master_guru` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_guru', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id_guru', true);
            $table->string('dapo_id', 50)->nullable();
            $table->string('ptk_id', 50)->nullable();
            $table->integer('id_user')->nullable();
            $table->char('nip', 30);
            $table->string('nama_guru', 50);
            $table->string('email', 254)->nullable();
            $table->string('kode_guru', 6)->nullable();
            $table->string('username', 50)->nullable();
            $table->longText('password')->nullable();
            $table->string('no_ktp', 16)->nullable();
            $table->string('tempat_lahir', 30)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('agama', 10)->nullable();
            $table->string('no_hp', 13)->nullable();
            $table->string('alamat_jalan', 255)->nullable();
            $table->string('rt_rw', 8)->nullable();
            $table->string('dusun', 50)->nullable();
            $table->string('kelurahan', 50)->nullable();
            $table->string('kecamatan', 30)->nullable();
            $table->string('kabupaten', 30)->nullable();
            $table->string('provinsi', 30)->nullable();
            $table->integer('kode_pos')->nullable();
            $table->string('kewarganegaraan', 10)->nullable();
            $table->string('nuptk', 20)->nullable();
            $table->string('jenis_ptk', 50)->nullable();
            $table->string('tgs_tambahan', 50)->nullable();
            $table->string('status_pegawai', 50)->nullable();
            $table->string('status_aktif', 20)->nullable();
            $table->string('status_nikah', 20)->nullable();
            $table->date('tmt')->nullable();
            $table->string('keahlian_isyarat', 10)->nullable();
            $table->string('npwp', 16)->nullable();
            $table->longText('foto')->nullable();
            $table->decimal('poin_tahunan', 10, 2)->nullable()->default(0.00);
            $table->string('api_key', 255)->nullable();
            $table->string('ttd', 255)->nullable();
            $table->longText('dokumen')->nullable();
            $table->string('sync_uuid', 36)->nullable();
            $table->enum('sync_status', ['synced', 'pending', 'conflict'])->nullable()->default('pending');
            $table->dateTime('sync_at')->nullable();
            $table->enum('sync_origin', ['pusat', 'lokal'])->nullable()->default('lokal');
            $table->string('sync_school_uuid', 50)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->string('telegram_id', 50)->nullable();
            $table->string('wa_number', 20)->nullable()->comment('Nomor WhatsApp guru (digit saja, kode negara tanpa 0)');
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->unique('id_user', 'uq_mg_id_user');
            $table->index('sync_uuid', $isMysql ? 'idx_sync_uuid' : 'master_guru_idx_sync_uuid_index');
            $table->index('sync_school_uuid', $isMysql ? 'sync_school_uuid' : 'master_guru_sync_school_uuid_index');
            $table->index('deleted_at', $isMysql ? 'idx_deleted_at' : 'master_guru_idx_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_guru');
    }
};

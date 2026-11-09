<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `setting` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Satu-satunya tabel latin1 di database produksi:
 * charset latin1 / collation latin1_swedish_ci.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->integer('id_setting', true);
            $table->string('kode_sekolah', 10)->nullable();
            $table->string('sekolah', 50)->nullable();
            $table->string('npsn', 10)->nullable();
            $table->string('nss', 20)->nullable();
            $table->integer('jenjang')->nullable();
            $table->string('kepsek', 50)->nullable();
            $table->string('nip', 30)->nullable();
            $table->string('proktor', 50)->nullable();
            $table->string('nip_proktor', 30)->nullable();
            $table->text('tanda_tangan')->nullable();
            $table->text('alamat')->nullable();
            $table->string('desa', 100)->nullable();
            $table->string('kecamatan', 50)->nullable();
            $table->string('kota', 30)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->integer('kode_pos')->nullable();
            $table->string('telp', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('web', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('nama_aplikasi', 100)->nullable();
            $table->text('logo_kanan')->nullable();
            $table->text('logo_kiri')->nullable();
            $table->text('kodescan')->nullable();
            $table->text('kopsekolah')->nullable();
            $table->text('stampel')->nullable();
            $table->text('ttdproktor')->nullable();
            $table->text('banner1')->nullable();
            $table->text('banner2')->nullable();
            $table->text('banner3')->nullable();
            $table->string('versi', 10)->nullable();
            $table->string('ip_server', 100)->nullable();
            $table->string('waktu', 50)->nullable();
            $table->string('server', 50)->nullable();
            $table->string('id_server', 50)->nullable();
            $table->string('sekolah_id', 50)->nullable();
            $table->string('db_versi', 10)->nullable();
            $table->string('satuan_pendidikan', 1)->nullable();
            $table->string('ba_aktif', 20)->nullable();
            $table->dateTime('ba_waktu')->nullable();
            $table->string('tkn_siswa', 11)->nullable();
            $table->string('mode_app', 11)->nullable();
            $table->text('api_key')->nullable();
            $table->string('sync_school_uuid', 50)->nullable();
            $table->integer('enable_ai')->nullable()->default(1);
            $table->string('surat_teguran_format', 120)->nullable()->default('SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}');
            $table->boolean('surat_teguran_padding')->nullable()->default(3);
            $table->string('theme_preset', 20)->default('default');
            $table->string('theme_custom', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting');
    }
};

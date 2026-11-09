<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `presensi_siswa`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260614000004_CreatePresensiSiswaTable.php
 *  - 20260617000002_AddAttendancePerformanceIndexes.php
 *  - 20260910000001_UpdatePresensiSiswaAddMasukKeluar.php
 *  - 2026-09-11-100000_AddLokasiToPresensiSiswa.php
 *  - 20261006000001_EnforceSatuBarisPerSiswaPerHari.php
 *  - 20261102000002_AddPulangTidakTercatatToPresensiSiswa.php
 *  - 20261106000000_AddDendaSiswaSettings.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_siswa', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->integer('id', true);
            $table->integer('id_kelas');
            $table->integer('id_siswa');
            $table->integer('id_mapel')->nullable();
            $table->integer('id_guru')->nullable();
            $table->date('tgl');
            $table->time('jam')->nullable();
            $table->enum('status', ['HADIR', 'SAKIT', 'IZIN', 'ALPA', 'TANPA_KETERANGAN'])->default('TANPA_KETERANGAN');
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->decimal('lat_masuk', 10, 7)->nullable();
            $table->decimal('lng_masuk', 11, 7)->nullable();
            $table->integer('akurasi_masuk')->nullable()->comment('Akurasi GPS meter');
            $table->integer('lokasi_id')->nullable()->comment('FK absensi_lokasi terdekat');
            $table->decimal('lat_keluar', 10, 7)->nullable();
            $table->decimal('lng_keluar', 11, 7)->nullable();
            $table->string('sumber_masuk', 20)->nullable();
            $table->string('sumber_keluar', 20)->nullable();
            $table->string('ip_masuk', 45)->nullable();
            $table->string('ip_keluar', 45)->nullable();
            $table->boolean('pulang_tidak_tercatat')->default(0);
            $table->decimal('denda', 10, 2)->default(0);
            $table->string('denda_jenis', 10)->default('');
            $table->string('keterangan_denda', 255)->nullable();
            $table->index(['id_kelas', 'id_siswa', 'tgl'], 'idx_siswa_kelas_tgl');
            $table->index(['id_kelas', 'tgl', 'status'], 'idx_kelas_tgl');
            $table->unique(['id_siswa', 'tgl'], 'uq_ps_siswa_tgl');
            $table->index(['id_kelas', 'tgl'], 'idx_ps_kelas_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_siswa');
    }
};

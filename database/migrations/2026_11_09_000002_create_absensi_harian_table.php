<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_harian`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-08-000001_CreateAttendanceTables.php
 *  - 20260110000000_UpdateAbsensiTables.php
 *  - 20260110020000_AddGpsAccuracyColumn.php
 *  - 20260110030000_AddSecurityColumns.php
 *  - 20260110060000_AddAttendanceColumns.php
 *  - 2026-02-04-000001_AddEarlyArrivalLateDepartureToAbsensi.php
 *  - 2026-02-09-224952_AddDendaToAbsensiHarian.php
 *  - 2026-02-10-234142_AddKeteranganToAbsensiHarian.php
 *  - 20260617000002_AddAttendancePerformanceIndexes.php
 *  - 20260619000001_AddWfhColumns.php
 *  - 20260909000001_AddPresensiUniqueConstraints.php
 *  - 20261019000000_HardenIzinKuota.php
 *  - 20261105000000_AddPenaltiLupaPulangGuru.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 * Skema disesuaikan dengan database produksi (dump absen.sql, 9 Nov 2026) — koreksi atas port awal dari migrasi CI4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_harian', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('user_id');
            $table->unsignedInteger('id_guru')->nullable()->default(0);
            $table->date('tgl');
            $table->dateTime('jam_masuk')->nullable();
            $table->decimal('lat_masuk', 10, 8)->nullable();
            $table->decimal('lng_masuk', 11, 8)->nullable();
            $table->integer('lokasi_masuk_id')->nullable();
            $table->integer('jarak_masuk')->nullable();
            $table->text('foto_masuk')->nullable();
            $table->string('status_masuk', 50)->nullable();
            $table->decimal('denda', 15, 2)->nullable()->default(0);
            $table->text('keterangan_denda')->nullable();
            $table->integer('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->boolean('is_wfh')->nullable()->default(0);
            $table->text('keterangan')->nullable();
            $table->integer('points')->nullable()->default(0);
            $table->integer('telat_menit')->nullable()->default(0);
            $table->integer('datang_awal_menit')->nullable()->default(0);
            $table->dateTime('jam_pulang')->nullable();
            $table->decimal('lat_pulang', 10, 8)->nullable();
            $table->decimal('lng_pulang', 11, 8)->nullable();
            $table->integer('lokasi_pulang_id')->nullable();
            $table->integer('jarak_pulang')->nullable();
            $table->text('foto_pulang')->nullable();
            $table->string('status_pulang', 50)->nullable();
            $table->integer('pulang_cepat_menit')->nullable()->default(0);
            $table->integer('pulang_akhir_menit')->nullable()->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->float('gps_accuracy', 24)->nullable()->comment('GPS accuracy in meters');
            $table->boolean('is_mock_location')->nullable()->default(0)->comment('Flag for suspected mock/fake GPS location');
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('catatan')->nullable();
            $table->string('device_info', 255)->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->unsignedInteger('izin_id')->nullable();
            $table->boolean('pulang_tidak_tercatat')->default(0);
            $table->integer('poin_penalti_pulang')->default(0);
            $table->index(['user_id', 'tgl'], 'idx_user_tgl');
            $table->index(['tgl', 'status_masuk'], 'idx_tgl_status');
            $table->index(['tgl', 'user_id', 'status_masuk'], 'idx_tgl_userid');
            $table->index(['user_id', 'denda'], 'idx_denda');
            $table->unique(['user_id', 'tgl'], 'uq_user_tgl');
            $table->index('izin_id', 'idx_harian_izin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_harian');
    }
};

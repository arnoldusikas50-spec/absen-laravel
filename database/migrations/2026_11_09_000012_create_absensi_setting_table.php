<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_setting`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-08-000001_CreateAttendanceTables.php
 *  - 20260110010000_AddHolidayTables.php
 *  - 20260110060000_AddAttendanceColumns.php
 *  - 20260111173000_AddNotificationSettings.php
 *  - 20260111201500_AddMikrotikSettings.php
 *  - 2026-02-11-100000_AddDendaColumnsToSettings.php
 *  - 20260617000003_AddAttendanceAlertColumns.php
 *  - 20260619000001_AddWfhColumns.php
 *  - 2026-09-11-110000_AddQrScanWindowToAbsensiSetting.php
 *  - 20261001120000_AddOpenWAGateway.php
 *  - 20261006000002_AddKioskTokenToAbsensiSetting.php
 *  - 20261019000000_HardenIzinKuota.php
 *  - 20261031000001_AddWajibJurnalPulangSetting.php
 *  - 20261102000001_EnforceSingleAbsensiSettingRow.php
 *  - 20261102000002_AddPulangTidakTercatatToPresensiSiswa.php
 *  - 20261104000000_AddWajibJurnalTervalidasiSetting.php
 *  - 20261105000000_AddPenaltiLupaPulangGuru.php
 *  - 20261106000000_AddDendaSiswaSettings.php
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
        Schema::create('absensi_setting', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->time('jam_masuk')->nullable()->default('07:00:00');
            $table->time('jam_pulang')->nullable()->default('15:00:00');
            $table->integer('radius_meter')->nullable()->default(50);
            $table->boolean('foto_wajib')->nullable()->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->string('mikrotik_host', 100)->nullable();
            $table->string('mikrotik_user', 100)->nullable();
            $table->string('mikrotik_pass', 100)->nullable();
            $table->integer('mikrotik_port')->nullable()->default(8728);
            $table->string('mikrotik_hotspot_profile', 100)->nullable()->default('default');
            $table->time('jam_masuk_jumat')->nullable();
            $table->time('jam_pulang_jumat')->nullable();
            $table->string('hari_kerja', 255)->nullable();
            $table->boolean('libur_kalender')->nullable()->default(1);
            $table->integer('max_izin_hari')->nullable()->default(3);
            $table->integer('max_cuti_hari')->nullable()->default(12);
            $table->integer('denda_tier_1')->nullable()->default(1000);
            $table->integer('denda_tier_2')->nullable()->default(2000);
            $table->integer('denda_tier_3')->nullable()->default(5000);
            $table->integer('point_tier_1')->nullable()->default(1);
            $table->integer('point_tier_2')->nullable()->default(2);
            $table->integer('point_tier_3')->nullable()->default(5);
            $table->integer('point_telat')->nullable()->default(1);
            $table->integer('point_alpa')->nullable()->default(5);
            $table->decimal('denda_per_point', 10, 2)->nullable()->default(1000.00);
            $table->decimal('max_denda_bulan', 10, 2)->nullable()->default(50000.00);
            $table->integer('grace_period')->nullable()->default(15);
            $table->integer('point_pulang_awal')->nullable()->default(1);
            $table->integer('point_hadir')->nullable()->default(10);
            $table->integer('point_izin')->nullable()->default(0);
            $table->integer('point_sakit')->nullable()->default(0);
            $table->integer('point_cuti')->nullable()->default(0);
            $table->integer('reward_cap_menit')->nullable()->default(30);
            $table->integer('reward_point_tier_1')->nullable()->default(1);
            $table->integer('reward_point_tier_2')->nullable()->default(1);
            $table->integer('reward_point_tier_3')->nullable()->default(2);
            $table->integer('denda_alpha_per_hari')->nullable()->default(5000);
            $table->decimal('min_kehadiran_persen', 5, 2)->nullable()->default(90.00)->comment('Standar SMA: min 90% kehadiran');
            $table->boolean('alert_kehadiran_enabled')->nullable()->default(1)->comment('Tampilkan alert di dashboard');
            $table->boolean('notif_kehadiran_rendah')->nullable()->default(1)->comment('Kirim notif ke kepsek jika rendah');
            $table->boolean('wfh_enabled')->nullable()->default(0);
            $table->time('wfh_mulai')->nullable()->default('08:00:00');
            $table->time('wfh_selesai')->nullable()->default('16:00:00');
            $table->integer('wfh_max_per_bulan')->nullable()->default(5);
            $table->boolean('wfh_wajib_wajah')->nullable()->default(1);
            $table->string('telegram_bot_token', 255)->nullable();
            $table->string('telegram_chat_id', 100)->nullable();
            $table->string('wa_gateway_url', 255)->nullable();
            $table->string('wa_token', 255)->nullable();
            $table->string('wa_target_number', 50)->nullable()->comment('Nomor WA tujuan laporan (contoh: 081234...)');
            $table->string('wa_gateway_mode', 20)->nullable()->default('off')->comment('off | openwa | fonnte');
            $table->string('wa_base_url', 255)->nullable()->comment('URL dasar OpenWA, mis. http://gateway:2785 (suffix /api ditambahkan otomatis)');
            $table->string('wa_api_key', 255)->nullable()->comment('X-API-Key OpenWA dengan role OPERATOR');
            $table->string('wa_session_name', 100)->nullable()->comment('Nama sesi OpenWA yang dipakai; kosong = ambil sesi ready pertama');
            $table->integer('point_lembur_per_jam')->nullable()->default(5);
            $table->decimal('face_threshold', 4, 2)->nullable()->default(0.72);
            $table->string('face_match_mode', 20)->nullable()->default('ai_match');
            $table->integer('denda_per_menit')->nullable()->default(1000);
            $table->integer('denda_pulang_awal')->nullable()->default(1000);
            $table->string('google_client_id', 255)->nullable();
            $table->string('google_client_secret', 255)->nullable();
            $table->boolean('google_login_enabled')->nullable()->default(1);
            $table->string('hari_libur_mingguan', 32)->nullable()->default('Sun');
            $table->boolean('qr_scan_aktif')->default(1);
            $table->time('qr_scan_mulai')->nullable()->default('05:30:00');
            $table->time('qr_scan_selesai')->nullable()->default('18:00:00');
            $table->boolean('denda_alpha_progresif')->nullable()->default(1);
            $table->decimal('denda_prog_h3', 5, 2)->nullable()->default(3.00);
            $table->decimal('denda_prog_h4', 5, 2)->nullable()->default(5.00);
            $table->decimal('denda_prog_h5', 5, 2)->nullable()->default(5.00);
            $table->decimal('denda_prog_h5_step', 5, 2)->nullable()->default(1.00);
            $table->string('qr_kiosk_token', 64)->nullable();
            $table->integer('max_izin_per_pengajuan')->nullable()->default(3);
            $table->integer('max_cuti_per_pengajuan')->nullable()->default(12);
            $table->integer('max_sakit_per_pengajuan')->nullable()->default(3);
            $table->integer('max_dinas_per_pengajuan')->nullable()->default(30);
            $table->integer('max_sakit_hari')->nullable()->default(12);
            $table->boolean('kuota_hari_kerja')->nullable()->default(1);
            $table->boolean('wajib_jurnal_sebelum_pulang')->nullable()->default(1);
            $table->tinyInteger('singleton_guard')->storedAs(1);
            $table->date('pulang_close_dari_tgl')->nullable();
            $table->boolean('wajib_jurnal_tervalidasi_sebelum_pulang')->nullable()->default(0);
            $table->integer('point_lupa_pulang')->nullable()->default(5);
            $table->date('lupa_pulang_dari_tgl')->nullable();
            $table->integer('denda_alpha_siswa_per_hari')->nullable()->default(0);
            $table->integer('denda_bolos_siswa_per_hari')->nullable()->default(0);
            $table->decimal('max_denda_siswa_bulan', 10, 2)->nullable()->default(0.00);
            $table->unique('singleton_guard', 'uq_absensi_setting_single_row');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_setting');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Port Tahap 1A dari CI4: app/Models/AttendanceSettingModel.php
 * Tabel: absensi_setting
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class AttendanceSettingModel extends Model
{
    protected $table = 'absensi_setting';

    protected $fillable = [
        'jam_masuk',
        'jam_pulang',
        'radius_meter',
        'foto_wajib',
        'hari_kerja',
        'hari_libur_mingguan',
        'libur_kalender',
        'max_izin_hari',
        'max_cuti_hari',
        'max_sakit_hari',
        'max_izin_per_pengajuan',
        'max_cuti_per_pengajuan',
        'max_sakit_per_pengajuan',
        'max_dinas_per_pengajuan',
        'kuota_hari_kerja',
        'point_hadir',
        'point_telat',
        'point_pulang_awal',
        'point_izin',
        'point_sakit',
        'point_cuti',
        'point_alpa',
        'point_lupa_pulang',
        'lupa_pulang_dari_tgl',
        'grace_period',
        'max_denda_bulan',
        'denda_per_menit',
        'denda_pulang_awal',
        'denda_tier_1',
        'denda_tier_2',
        'denda_tier_3',
        'denda_alpha_per_hari',
        'denda_alpha_siswa_per_hari',
        'denda_bolos_siswa_per_hari',
        'max_denda_siswa_bulan',
        'point_tier_1',
        'point_tier_2',
        'point_tier_3',
        'reward_cap_menit',
        'reward_point_tier_1',
        'reward_point_tier_2',
        'reward_point_tier_3',
        'telegram_bot_token',
        'telegram_chat_id',
        'wa_gateway_url',
        'wa_token',
        'wa_target_number',
        'wa_gateway_mode',
        'wa_base_url',
        'wa_api_key',
        'wa_session_name',
        'min_kehadiran_persen',
        'alert_kehadiran_enabled',
        'notif_kehadiran_rendah',
        'wfh_enabled',
        'wfh_mulai',
        'wfh_selesai',
        'wfh_max_per_bulan',
        'wfh_wajib_wajah',
        'wajib_jurnal_sebelum_pulang',
        'wajib_jurnal_tervalidasi_sebelum_pulang',
        'point_lembur_per_jam',
        'face_threshold',
        'face_match_mode',
        'denda_per_point',
        'qr_scan_aktif',
        'qr_scan_mulai',
        'qr_scan_selesai',
        'qr_kiosk_token',
        'google_client_id',
        'google_client_secret',
        'google_login_enabled',
    ];

    protected $casts = [
        'id' => 'integer',
        'radius_meter' => 'integer',
        'foto_wajib' => 'boolean',
        'hari_kerja' => 'array',
        'libur_kalender' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'mikrotik_port' => 'integer',
        'denda_per_menit' => 'decimal:2',
        'denda_pulang_awal' => 'decimal:2',
        'min_kehadiran_persen' => 'decimal:2',
        'alert_kehadiran_enabled' => 'boolean',
        'notif_kehadiran_rendah' => 'boolean',
        'wfh_enabled' => 'boolean',
        'wfh_max_per_bulan' => 'integer',
        'wfh_wajib_wajah' => 'boolean',
        'qr_scan_aktif' => 'boolean',
        'max_izin_per_pengajuan' => 'integer',
        'max_cuti_per_pengajuan' => 'integer',
        'max_sakit_per_pengajuan' => 'integer',
        'max_dinas_per_pengajuan' => 'integer',
        'max_sakit_hari' => 'integer',
        'kuota_hari_kerja' => 'boolean',
        'wajib_jurnal_sebelum_pulang' => 'boolean',
        'singleton_guard' => 'integer',
        'pulang_close_dari_tgl' => 'date',
        'wajib_jurnal_tervalidasi_sebelum_pulang' => 'boolean',
        'point_lupa_pulang' => 'integer',
        'lupa_pulang_dari_tgl' => 'date',
        'denda_alpha_siswa_per_hari' => 'integer',
        'denda_bolos_siswa_per_hari' => 'integer',
        'max_denda_siswa_bulan' => 'decimal:2',
    ];

    /**
     * Baris utama = id terkecil.
     *
     * Tabel ini pernah terisi ganda, jadi `first()` tanpa ORDER BY
     * mengembalikan baris yang bergantung pada urutan fisik tabel — nilai
     * yang bisa berbeda antar-request untuk isi yang identik. Itu causes
     * pengaturan yang dibaca cron (min_kehadiran_persen,
     * notif_kehadiran_rendah, hari_libur_mingguan, point_alpa, denda_alpha_per_hari)
     * ikut tidak stabil.
     *
     * NotificationService sudah lama memasang bumper untuk 9 kolom kredensialnya
     * dengan cara yang sama ("Baris utama (id terkecil) tetap dipakai").
     * Menorderkan di sini membuat seluruh pembacaan ikut aturan yang sama,
     * bukan cuma kolom notifikasi.
     */
    // NOTE: Nama ini membayangi first() bawaan Eloquent. Di Eloquent,
    // first() didefinisikan di query builder (Builder::first($columns)),
    // bukan di Model — Model hanya meneruskannya lewat __call. Di sini
    // first() di-port persis seperti CI4 (method instance tanpa parameter,
    // selalu mengembalikan baris id terkecil). Semua call site di aplikasi
    // memanggilnya sebagai instance (new AttendanceSettingModel())->first()
    // tanpa argumen dan memakai hasilnya sebagai object ($setting->...),
    // jadi perilaku itu dipertahankan. Cabang CI4 "bila orderBy sudah
    // di-set, pertahankan" tidak ikut di-port: di Eloquent, orderBy()
    // pada model mengembalikan Builder sehingga ->first() berikutnya
    // adalah Builder::first() dan tidak pernah sampai ke method ini.
    // Jangan panggil AttendanceSettingModel::first() secara statis
    // (method non-statis) dan jangan oper argumen kolom ke method ini;
    // untuk itu pakai static::query()->orderBy(...)->first($columns).
    public function first()
    {
        return static::query()->orderBy('id', 'ASC')->first();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/NotifChannelConfigModel.php
 * Tabel: notif_channel_config
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class NotifChannelConfigModel extends Model
{
    protected $table = 'notif_channel_config';

    protected $fillable = [
        'jenis',
        'tg_grup',
        'wa_grup',
        'tg_personal',
        'wa_personal',
    ];

    protected $casts = [
        'id' => 'integer',
        'tg_grup' => 'boolean',
        'wa_grup' => 'boolean',
        'tg_personal' => 'boolean',
        'wa_personal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Channel yang dikenal (kolom di tabel). */
    public const CHANNELS = ['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'];

    /** Label tampilan untuk setiap channel. */
    public const CHANNEL_LABELS = [
        'tg_grup' => 'TG Grup',
        'wa_grup' => 'WA Grup',
        'tg_personal' => 'TG Personal',
        'wa_personal' => 'WA Personal',
    ];

    /**
     * Semua jenis notifikasi yang dikenal beserta metadatanya.
     *
     * 'personal' => true  : jenis ini bisa dikirim ke personal guru
     * 'label'             : nama ramah untuk UI
     * 'pref_key'          : kunci di user_notifikasi.prefs ('' bila tidak ada)
     */
    public const JENIS_LIST = [
        'keterlambatan' => ['label' => 'Keterlambatan Check-in', 'personal' => true, 'pref_key' => 'kehadiran'],
        'denda' => ['label' => 'Denda Baru', 'personal' => true, 'pref_key' => 'denda'],
        'izin' => ['label' => 'Izin / Sakit / Cuti (semua)', 'personal' => true, 'pref_key' => 'izin'],
        'koreksi' => ['label' => 'Koreksi Jam Absensi (semua)', 'personal' => true, 'pref_key' => 'koreksi'],
        'alpha' => ['label' => 'Alpha / Tidak Hadir (real-time; cron harian saat ini hanya mengirim Surat Teguran)', 'personal' => true, 'pref_key' => 'alpha'],
        'teguran' => ['label' => 'Surat Teguran', 'personal' => true, 'pref_key' => 'teguran'],
        'panggilan' => ['label' => 'Surat Panggilan Orang Tua', 'personal' => true, 'pref_key' => 'panggilan'],
        'dinas' => ['label' => 'Dinas (semua)', 'personal' => true, 'pref_key' => 'izin'],
        'guru_terawal' => ['label' => 'Guru Paling Cepat Hadir', 'personal' => true, 'pref_key' => 'guru_terawal'],
        // Grup-only
        'rekap_harian' => ['label' => 'Rekap Harian (Cron)', 'personal' => false, 'pref_key' => ''],
        'rekap_denda_mingguan' => ['label' => 'Rekap Denda Mingguan (Cron)', 'personal' => false, 'pref_key' => ''],
        'rekap_denda_bulanan' => ['label' => 'Rekap Denda Bulanan (PDF tgl 1, tombol kirim)', 'personal' => false, 'pref_key' => ''],
        'rekap_bulanan' => ['label' => 'Rekap Presensi Bulanan (PDF tgl 1, tombol kirim)', 'personal' => false, 'pref_key' => ''],
        'ucapan_pagi' => ['label' => 'Ucapan Pagi (Cron)', 'personal' => false, 'pref_key' => ''],
        'ucapan_sore' => ['label' => 'Ucapan Sore (Cron, hari kerja saja)', 'personal' => false, 'pref_key' => ''],
        'lupa_checkout' => ['label' => 'Lupa Check-out (Cron)', 'personal' => false, 'pref_key' => ''],
        'kehadiran_rendah' => ['label' => 'Kehadiran Rendah (Cron)', 'personal' => false, 'pref_key' => ''],
        'rekap_piket' => ['label' => 'Rekap Piket Harian (tombol kirim)', 'personal' => false, 'pref_key' => ''],
        'apel_kesadaran' => ['label' => 'Pengingat Apel Kesadaran KORPRI (Tgl 16, Cron)', 'personal' => false, 'pref_key' => ''],
    ];

    /** @var array<string,object>|null Indexed by jenis, null = belum dimuat */
    private ?array $cache = null;

    /**
     * Ambil semua konfigurasi channel sebagai map [jenis => object].
     *
     * Jenis yang belum punya baris di DB akan di-seed dengan default
     * (personal = 1 bila jenis bisa personal, 0 bila grup-only).
     * Hasilnya di-cache in-request supaya setiap pemanggil notify*() di
     * NotificationService tidak memukul DB lebih dari satu kali per request.
     *
     * @return array<string,object>
     */
    public function getConfig(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            if (! Schema::hasTable($this->getTable())) {
                // Tabel belum ada (migrate belum dijalankan) — kembalikan default
                return $this->cache = $this->buildDefaults();
            }

            $rows = static::query()->get()->all();
            $map = [];
            foreach ($rows as $row) {
                $map[$row->jenis] = $row;
            }

            // Seed jenis yang belum ada baris-nya
            $this->seedMissing($map);

            // Reload setelah seed supaya baris baru ikut masuk
            if (count($map) < count(self::JENIS_LIST)) {
                $rows = static::query()->get()->all();
                $map = [];
                foreach ($rows as $row) {
                    $map[$row->jenis] = $row;
                }
            }

            return $this->cache = $map;
        } catch (\Throwable $e) {
            Log::error('[NotifChannelConfig] getConfig gagal: '.$e->getMessage());

            return $this->cache = $this->buildDefaults();
        }
    }

    /**
     * Apakah channel tertentu aktif untuk suatu jenis notifikasi?
     *
     * @param  string  $jenis  Mis. 'denda', 'izin', dsb.
     * @param  string  $channel  Mis. 'tg_grup', 'wa_personal', dsb.
     */
    public function isEnabled(string $jenis, string $channel): bool
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            return false;
        }
        $cfg = $this->getConfig();
        if (! isset($cfg[$jenis])) {
            return true; // jenis tidak dikenal → default aktif (fail-open)
        }

        return (int) ($cfg[$jenis]->$channel ?? 1) === 1;
    }

    /**
     * Simpan konfigurasi dari array yang sudah disanitasi.
     *
     * Format input: ['keterlambatan' => ['tg_grup' => 1, 'wa_grup' => 0, ...], ...]
     *
     * PENTING: nilai di dalam array HARUS sudah berupa 0 atau 1 untuk
     * setiap channel, karena key selalu ada — termasuk saat checkbox-nya
     * tidak dicentang. Memakai isset() untuk membaca nyala/mati akan
     * selalu mengembalikan true karena key-nya ada, sehingga semua
     * channel tersimpan 1 dan admin tidak pernah bisa mematikan satu
     * pun. Sanitasi itu milik controller (PresensiAdmin::saveNotifConfig)
     * dan model ini dipakai juga oleh CLI/cron tanpa controller.
     *
     * @param  array<string,array<string,int>>  $data
     */
    public function saveConfig(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $ok = true;

        try {
            if (! Schema::hasTable($this->getTable())) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        foreach (self::JENIS_LIST as $jenis => $meta) {
            if (! isset($data[$jenis])) {
                continue;
            }
            $vals = $data[$jenis];

            $update = [];
            foreach (self::CHANNELS as $ch) {
                // Jenis grup-only: paksa personal tetap 0
                if (! $meta['personal'] && in_array($ch, ['tg_personal', 'wa_personal'], true)) {
                    $update[$ch] = 0;

                    continue;
                }

                // Baca NILAINYA, bukan keberadaan key-nya. Nilai selain 1
                // dianggap mati supaya kolom tidak pernah menyimpan nilai
                // di luar 0/1.
                $update[$ch] = (int) ($vals[$ch] ?? 0) === 1 ? 1 : 0;
            }
            $update['updated_at'] = $now;

            try {
                $existing = static::query()->where('jenis', $jenis)->first();
                if ($existing) {
                    $ok = static::query()->where('id', $existing->id)->update($update) >= 0 && $ok;
                } else {
                    $update['jenis'] = $jenis;
                    $update['created_at'] = $now;
                    $ok = (bool) static::query()->insert($update) && $ok;
                }
            } catch (\Throwable $e) {
                Log::error('[NotifChannelConfig] saveConfig gagal untuk '.$jenis.': '.$e->getMessage());
                $ok = false;
            }
        }

        // Invalidasi cache in-request setelah simpan
        $this->cache = null;

        return $ok;
    }

    /**
     * Seed baris yang belum ada di DB agar tabel tidak pernah kosong.
     *
     * @param  array<string,object>  $existing  Baris yang sudah ada (by jenis)
     */
    private function seedMissing(array $existing): void
    {
        $now = date('Y-m-d H:i:s');
        $seed = [];

        foreach (self::JENIS_LIST as $jenis => $meta) {
            if (isset($existing[$jenis])) {
                continue;
            }
            $canPersonal = $meta['personal'] ? 1 : 0;
            $seed[] = [
                'jenis' => $jenis,
                'tg_grup' => 1,
                'wa_grup' => 1,
                'tg_personal' => $canPersonal,
                'wa_personal' => $canPersonal,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($seed !== []) {
            try {
                DB::table($this->getTable())->insert($seed);
            } catch (\Throwable $e) {
                Log::error('[NotifChannelConfig] seedMissing gagal: '.$e->getMessage());
            }
        }
    }

    /**
     * Bangun map default in-memory (tanpa menyentuh DB).
     * Dipakai sebagai fallback saat tabel belum ada atau query gagal.
     *
     * @return array<string,object>
     */
    private function buildDefaults(): array
    {
        $map = [];
        foreach (self::JENIS_LIST as $jenis => $meta) {
            $canPersonal = $meta['personal'] ? 1 : 0;
            $map[$jenis] = (object) [
                'jenis' => $jenis,
                'tg_grup' => 1,
                'wa_grup' => 1,
                'tg_personal' => $canPersonal,
                'wa_personal' => $canPersonal,
            ];
        }

        return $map;
    }
}

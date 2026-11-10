<?php

/**
 * Init Helper - Migrated from CI3
 * Helper functions for initialization checks
 *
 * Port dari CodeIgniter 4 ke Laravel 13. \Config\Database::connect()
 * diganti DB/Schema facade; service('session') diganti helper session().
 */

use App\Models\DashboardModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('database_connected')) {
    /**
     * Check if database is connected
     */
    function database_connected(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('is_database_empty')) {
    /**
     * Check if database has required tables
     */
    function is_database_empty(): bool
    {
        try {
            $requiredTables = ['users', 'setting', 'groups'];

            foreach ($requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            return true;
        }
    }
}

if (! function_exists('is_setting_empty')) {
    /**
     * Check if settings table has data
     */
    function is_setting_empty(): bool
    {
        try {
            return DB::table('setting')->count() === 0;
        } catch (Exception $e) {
            return true;
        }
    }
}

if (! function_exists('get_setting')) {
    /**
     * Get application settings
     */
    function get_setting(): ?object
    {
        try {
            return DB::table('setting')->first();
        } catch (Exception $e) {
            return null;
        }
    }
}

if (! function_exists('uri_string')) {
    /**
     * CI4 uri_string() — path request saat ini tanpa query string,
     * dipakai view porting untuk mencocokkan menu aktif.
     */
    function uri_string(): string
    {
        return request()->path();
    }
}

if (! function_exists('esc')) {
    /**
     * Port helper esc() CodeIgniter 4 → Laravel.
     *
     * Banyak view hasil porting masih memanggil esc(). Laravel tidak
     * menyediakan helper tersebut sehingga halaman gagal dirender dengan
     * "Call to undefined function esc()". Implementasi ini mengikuti
     * kontrak CI4: html, attr, js, css, dan url.
     *
     * @param  mixed  $data
     */
    function esc($data, string $context = 'html', ?string $encoding = null): string
    {
        if (is_array($data) || is_object($data)) {
            return '';
        }

        $data = (string) $data;
        $encoding = $encoding ?? 'UTF-8';

        switch ($context) {
            case 'js':
                return substr(
                    json_encode(
                        $data,
                        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE
                    ),
                    1,
                    -1
                );

            case 'css':
                return preg_replace('/([^a-zA-Z0-9\-.])/', '\\\\$1', $data) ?? '';

            case 'url':
                return rawurlencode($data);

            case 'attr':
            case 'html':
            default:
                return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $encoding, true);
        }
    }
}

if (! function_exists('tp_aktif')) {
    /**
     * Get active tahun pelajaran
     */
    function tp_aktif(): ?int
    {
        $setting = get_setting();

        return $setting ? (int) $setting->tp_aktif : null;
    }
}

if (! function_exists('smt_aktif')) {
    /**
     * Get active semester
     */
    function smt_aktif(): ?int
    {
        $setting = get_setting();

        return $setting ? (int) $setting->smt_aktif : null;
    }
}

if (! function_exists('singkat_tanggal')) {
    function singkat_tanggal($str)
    {
        $str = str_replace('Jan', 'Jan', $str);
        $str = str_replace('Feb', 'Feb', $str);
        $str = str_replace('Mar', 'Mar', $str);
        $str = str_replace('Apr', 'Apr', $str);
        $str = str_replace('May', 'Mei', $str);
        $str = str_replace('Jun', 'Jun', $str);
        $str = str_replace('Jul', 'Jul', $str);
        $str = str_replace('Aug', 'Agt', $str);
        $str = str_replace('Sep', 'Sep', $str);
        $str = str_replace('Oct', 'Okt', $str);
        $str = str_replace('Nov', 'Nov', $str);
        $str = str_replace('Dec', 'Des', $str);
        $str = str_replace('Mon', 'Senin', $str);
        $str = str_replace('Tue', 'Selasa', $str);
        $str = str_replace('Wed', 'Rabu', $str);
        $str = str_replace('Thu', 'Kamis', $str);
        $str = str_replace('Fri', 'Jumat', $str);
        $str = str_replace('Sat', 'Sabtu', $str);
        $str = str_replace('Sun', 'Minggu', $str);

        return $str;
    }
}

if (! function_exists('buat_tanggal')) {
    /**
     * Convert English date format to Indonesian
     * Translates day names (Mon, Tue, etc.) and month names (Jan, Feb, etc.)
     * to their Indonesian equivalents
     */
    function buat_tanggal($str)
    {
        // Translate months
        $str = str_replace('January', 'Januari', $str);
        $str = str_replace('February', 'Februari', $str);
        $str = str_replace('March', 'Maret', $str);
        $str = str_replace('April', 'April', $str);
        $str = str_replace('May', 'Mei', $str);
        $str = str_replace('June', 'Juni', $str);
        $str = str_replace('July', 'Juli', $str);
        $str = str_replace('August', 'Agustus', $str);
        $str = str_replace('September', 'September', $str);
        $str = str_replace('October', 'Oktober', $str);
        $str = str_replace('November', 'November', $str);
        $str = str_replace('December', 'Desember', $str);

        // Translate short months
        $str = str_replace('Jan', 'Jan', $str);
        $str = str_replace('Feb', 'Feb', $str);
        $str = str_replace('Mar', 'Mar', $str);
        $str = str_replace('Apr', 'Apr', $str);
        $str = str_replace('Jun', 'Jun', $str);
        $str = str_replace('Jul', 'Jul', $str);
        $str = str_replace('Aug', 'Agt', $str);
        $str = str_replace('Sep', 'Sep', $str);
        $str = str_replace('Oct', 'Okt', $str);
        $str = str_replace('Nov', 'Nov', $str);
        $str = str_replace('Dec', 'Des', $str);

        // Translate days
        $str = str_replace('Monday', 'Senin', $str);
        $str = str_replace('Tuesday', 'Selasa', $str);
        $str = str_replace('Wednesday', 'Rabu', $str);
        $str = str_replace('Thursday', 'Kamis', $str);
        $str = str_replace('Friday', 'Jumat', $str);
        $str = str_replace('Saturday', 'Sabtu', $str);
        $str = str_replace('Sunday', 'Minggu', $str);

        // Translate short days
        $str = str_replace('Mon', 'Sen', $str);
        $str = str_replace('Tue', 'Sel', $str);
        $str = str_replace('Wed', 'Rab', $str);
        $str = str_replace('Thu', 'Kam', $str);
        $str = str_replace('Fri', 'Jum', $str);
        $str = str_replace('Sat', 'Sab', $str);
        $str = str_replace('Sun', 'Min', $str);

        return $str;
    }
}
if (! function_exists('maybe_unserialize')) {
    function maybe_unserialize($original)
    {
        if (is_string($original)) {
            // Data berasal dari kolom DB; jangan izinkan objek PHP dihidrasi.
            $data = @unserialize($original, ['allowed_classes' => false]);
            if ($data !== false || $original === 'b:0;') {
                return $data;
            }

            // check if it's JSON
            $data = json_decode($original);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return $original;
    }
}

if (! function_exists('is_kepsek')) {
    /**
     * Deteksi akun Kepala Sekolah. Sumber berlapis mengikuti
     * PresensiSiswaQr::isKepalaSekolah agar hak akses konsisten:
     * role/grup, tugas tambahan, jabatan struktural, lalu kecocokan
     * nama/NIP dengan profil sekolah.
     */
    function is_kepsek(?int $userId = null): bool
    {
        $userId = $userId ?? (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        static $cache = [];
        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        if (in_array($roleName, ['kepsek', 'kepala sekolah', 'kepala_sekolah'], true)) {
            return $cache[$userId] = true;
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return $cache[$userId] = false;
        }

        $containsKepsek = static function (?string $v): bool {
            $v = strtolower(trim((string) $v));

            return $v !== '' && (str_contains($v, 'kepala sekolah') || str_contains($v, 'kepsek'));
        };

        // 1. users.tugas_tambahan
        try {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'tugas_tambahan')) {
                $row = DB::table('users')->select('tugas_tambahan')->where('id', $userId)->first();
                if ($row && $containsKepsek($row->tugas_tambahan ?? null)) {
                    return $cache[$userId] = true;
                }
            }
        } catch (Throwable $e) {
        }

        // 2. master_guru.tugas_tambahan / tgs_tambahan
        $guruRow = null;
        try {
            if (Schema::hasTable('master_guru')) {
                $cols = ['id_guru', 'nama_guru', 'nip'];
                if (Schema::hasColumn('master_guru', 'tugas_tambahan')) {
                    $cols[] = 'tugas_tambahan';
                }
                if (Schema::hasColumn('master_guru', 'tgs_tambahan')) {
                    $cols[] = 'tgs_tambahan';
                }
                $guruRow = DB::table('master_guru')->select($cols)->where('id_user', $userId)->first();
                if ($guruRow) {
                    if ($containsKepsek($guruRow->tugas_tambahan ?? null) || $containsKepsek($guruRow->tgs_tambahan ?? null)) {
                        return $cache[$userId] = true;
                    }
                }
            }
        } catch (Throwable $e) {
        }

        // 3. Jabatan struktural (id_jabatan = 1 atau level mengandung kepsek)
        try {
            if (Schema::hasTable('jabatan_guru') && $guruRow && ! empty($guruRow->id_guru)) {
                $jg = DB::table('jabatan_guru')->select('id_jabatan')
                    ->where('id_guru', (int) $guruRow->id_guru)->get();
                foreach ($jg as $r) {
                    if ((int) ($r->id_jabatan ?? 0) === 1) {
                        return $cache[$userId] = true;
                    }
                }
            }
            if (Schema::hasTable('master_guru') && Schema::hasTable('jabatan_guru') && Schema::hasTable('level_guru')) {
                $row = DB::table('master_guru as a')
                    ->select('lg.level as jabatan')
                    ->leftJoin('jabatan_guru as jg', 'jg.id_guru', '=', 'a.id_guru')
                    ->leftJoin('level_guru as lg', 'lg.id_level', '=', 'jg.id_jabatan')
                    ->where('a.id_user', $userId)
                    ->first();
                if ($row && $containsKepsek($row->jabatan ?? null)) {
                    return $cache[$userId] = true;
                }
            }
        } catch (Throwable $e) {
        }

        // 4. Kecocokan nama / NIP dengan profil sekolah
        try {
            if ($guruRow) {
                $setting = (new DashboardModel)->getSetting();
                $kepsekNama = strtolower(trim((string) ($setting->kepsek ?? '')));
                $kepsekNip = trim((string) ($setting->nip ?? ''));
                $namaGuru = strtolower(trim((string) ($guruRow->nama_guru ?? '')));
                if ($kepsekNama !== '' && $namaGuru !== ''
                    && ($namaGuru === $kepsekNama || str_contains($namaGuru, $kepsekNama) || str_contains($kepsekNama, $namaGuru))) {
                    return $cache[$userId] = true;
                }
                $guruNip = trim((string) ($guruRow->nip ?? ''));
                if ($kepsekNip !== '' && $guruNip !== '' && $guruNip === $kepsekNip) {
                    return $cache[$userId] = true;
                }
            }
        } catch (Throwable $e) {
        }

        return $cache[$userId] = false;
    }
}

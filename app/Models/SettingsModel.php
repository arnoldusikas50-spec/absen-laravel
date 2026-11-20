<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Tabel: setting
 */
class SettingsModel extends Model
{
    protected $table = 'setting';

    protected $primaryKey = 'id_setting';

    protected $fillable = [
        'nama_aplikasi',
        'sekolah',
        'nss',
        'npsn',
        'jenjang',
        'satuan_pendidikan',
        'alamat',
        'desa',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'telp',
        'fax',
        'email',
        'web',
        'kepsek',
        'nip',
        'proktor',
        'nip_proktor',
        'logo_kiri',
        'logo_kanan',
        'stampel',
        'tanda_tangan',
        'ttdproktor',
        'kodescan',
        'kopsekolah',
        'banner1',
        'banner2',
        'banner3',
        'kartu_bg_depan',
        'kartu_bg_belakang',
        'tp_aktif',
        'smt_aktif',
        'tkn_siswa',
        'mode_app',
        'ba_aktif',
        'ba_waktu',
        'api_key',
        'sync_school_uuid',
        'kode_sekolah',
        'surat_teguran_format',
        'surat_teguran_padding',
        'theme_preset',
        'theme_custom',
    ];

    public $timestamps = false;

    /**
     * Get current settings
     * Adds compatibility aliases for property names used in different views
     */
    public function getSetting(): ?object
    {
        $setting = static::query()->first();

        if ($setting) {
            // Add aliases for compatibility with views
            if (isset($setting->sekolah) && ! isset($setting->nama_sekolah)) {
                $setting->nama_sekolah = $setting->sekolah;
            }
            if (isset($setting->logo_kiri) && ! isset($setting->logo)) {
                $setting->logo = $setting->logo_kiri;
            }
            if (isset($setting->web) && ! isset($setting->website)) {
                $setting->website = $setting->web;
            }
        }

        return $setting;
    }

    /**
     * Update settings
     */
    public function updateSetting(array $data): bool
    {
        $setting = static::query()->first();

        if ($setting) {
            return (bool) $setting->update($data);
        }

        return static::query()->insert($data);
    }

    /**
     * Get active tahun pelajaran
     */
    public function getActiveTp(): ?int
    {
        $setting = static::query()->first();

        return $setting ? (int) $setting->tp_aktif : null;
    }

    /**
     * Get active semester
     */
    public function getActiveSmt(): ?int
    {
        $setting = static::query()->first();

        return $setting ? (int) $setting->smt_aktif : null;
    }

    /**
     * Kosongkan tabel yang diberikan lalu bersihkan data turunan.
     *
     * @param  array<int, string>  $tables
     */
    public function truncateTablesAndCleanup(array $tables): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $tb) {
            DB::table($tb)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $bankSoalPath = public_path('uploads/bank_soal');
        if (File::isDirectory($bankSoalPath)) {
            File::cleanDirectory($bankSoalPath);
        }

        // Delete non-admin users
        $users = DB::table('users as a')
            ->select('a.id')
            ->join('users_groups as b', 'a.id', '=', 'b.user_id')
            ->whereNotIn('b.group_id', ['1'])
            ->get();

        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->delete();
        }
    }

    /**
     * Get row count
     */
    public function rowSize($table)
    {
        return DB::table($table)->count();
    }

    /**
     * Kembalikan isi tabel sebagai JSON terformat.
     */
    public function tableToJson(string $table): string
    {
        return (string) json_encode(DB::table($table)->get()->all(), JSON_PRETTY_PRINT);
    }
}

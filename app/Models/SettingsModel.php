<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Port Tahap 1A dari CI4: app/Models/SettingsModel.php
 * Tabel: setting
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
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
    // TODO: tabel setting belum ada migrasinya
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
    // TODO: tabel setting belum ada migrasinya
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
    // TODO: tabel setting belum ada migrasinya
    public function getActiveTp(): ?int
    {
        $setting = static::query()->first();

        return $setting ? (int) $setting->tp_aktif : null;
    }

    /**
     * Get active semester
     */
    // TODO: tabel setting belum ada migrasinya
    public function getActiveSmt(): ?int
    {
        $setting = static::query()->first();

        return $setting ? (int) $setting->smt_aktif : null;
    }

    /**
     * Truncate tables and cleanup
     *
     * @param  array  $tables
     */
    // NOTE: nama method ini men-shadow truncate() bawaan Eloquent: pemanggilan
    // ->truncate() pada model ini (atau secara statis) yang di Eloquent
    // mengosongkan tabel model, di sini malah menjalankan logika CI4
    // (truncate daftar tabel yang diberikan + hapus file bank soal + hapus
    // user non-admin). Tidak ada call site di aplikasi Laravel yang memakai
    // truncate() Eloquent pada model ini maupun truncate($tables) CI4,
    // sehingga port apa adanya dengan nama persis tetap aman.
    // TODO: tabel users, users_groups belum ada migrasinya
    public function truncate($tables)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $tb) {
            DB::table($tb)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // Delete bank soal files (CI4: helper('filesystem') + delete_files()
        // pada FCPATH . 'uploads/bank_soal/'; FCPATH = public/ di Laravel).
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
     * Get table content as JSON (CI4: toJSON($table)).
     *
     * NOTE: nama method ini sama dengan Eloquent Model::toJson($options = 0)
     * (nama method PHP tidak case-sensitive), jadi signaturenya harus
     * kompatibel dengan parent. Bila dipanggil dengan string nama tabel,
     * berperilaku seperti CI4 toJSON($table); bila dipanggil tanpa argumen
     * atau dengan int opsi JSON, didelegasikan ke parent::toJson().
     */
    public function toJson($options = 0)
    {
        if (is_string($options)) {
            return json_encode(DB::table($options)->get()->all(), JSON_PRETTY_PRINT);
        }

        return parent::toJson($options);
    }
}

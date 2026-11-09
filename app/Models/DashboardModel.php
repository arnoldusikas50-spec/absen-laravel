<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/DashboardModel.php
 * Tabel: setting
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (setting).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class DashboardModel extends Model
{
    protected $table = 'setting';

    protected $fillable = [
    ];

    public $timestamps = false;

    /**
     * Get application settings
     * Adds compatibility aliases for property names used in different views
     */
    // TODO: tabel setting belum ada migrasinya
    public function getSetting(): ?object
    {
        try {
            if (! Schema::hasTable('setting')) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        $setting = DB::table('setting')->first();

        if ($setting) {
            // Add aliases for compatibility with views
            // CI3 uses 'sekolah', but some CI4 views use 'nama_sekolah'
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
     * Alias getSetting() dengan nama helper BaseController::getSettingRow().
     * NOTE: bukan method CI4; 4 call site Laravel memanggil getSettingRow()
     * langsung pada instance DashboardModel (Api/TelegramBotController,
     * Api/WhatsappWebhookController, Backend/AuthController,
     * Guru/AttendanceController), jadi alias ini diperlukan agar
     * pemanggilan tersebut tidak gagal (BadMethodCallException).
     */
    public function getSettingRow(): ?object
    {
        return $this->getSetting();
    }

    /**
     * Get active tahun pelajaran
     */
    // TODO: tabel master_tp belum ada migrasinya
    public function getTahunActive(): ?object
    {
        return DB::table('master_tp')
            ->where('active', 1)
            ->first();
    }

    /**
     * Get active semester
     */
    // TODO: tabel master_smt belum ada migrasinya
    public function getSemesterActive(): ?object
    {
        return DB::table('master_smt')
            ->where('active', 1)
            ->first();
    }

    /**
     * Get all tahun pelajaran
     */
    // TODO: tabel master_tp belum ada migrasinya
    public function getTahun(): array
    {
        return DB::table('master_tp')
            ->orderBy('id_tp', 'DESC')
            ->get()
            ->all();
    }

    /**
     * Get all semesters
     */
    // TODO: tabel master_smt belum ada migrasinya
    public function getSemester(): array
    {
        return DB::table('master_smt')
            ->get()
            ->all();
    }

    /**
     * Count total records in a table
     */
    public function total(string $table, ?string $where = null): int
    {
        if (empty($table)) {
            return 0;
        }
        $query = DB::table($table);

        if ($where) {
            $query->whereRaw($where);
        }

        return $query->count();
    }

    /**
     * Get total siswa per page
     */
    // TODO: tabel kelas_siswa, buku_induk belum ada migrasinya
    public function getTotalSiswa(int $tp, int $smt): int
    {
        return DB::table('kelas_siswa as a')
            ->join('buku_induk as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('b.status', '1')
            ->count();
    }

    /**
     * Get total wali kelas (teachers assigned to classes)
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function totalWaliKelas(int $tp, int $smt): int
    {
        try {
            return DB::table('master_kelas')
                ->where('id_tp', $tp)
                ->where('id_smt', $smt)
                ->whereRaw('guru_id IS NOT NULL')
                ->where('guru_id', '!=', 0)
                ->count();
        } catch (\Throwable $e) {
            // If column doesn't exist, return 0
            return 0;
        }
    }

    /**
     * Get total jadwal ujian
     */
    public function totalJadwal(int $tp, int $smt): int
    {
        return 0;
    }

    /**
     * Get data siswa by username
     */
    // TODO: tabel master_siswa, kelas_siswa, master_kelas belum ada migrasinya
    public function getDataSiswa(string $username, int $tp, int $smt): ?object
    {
        return DB::table('master_siswa as a')
            ->select('a.*', 'b.id_kelas', 'c.nama_kelas', 'c.kode_kelas', 'c.level_id')
            ->join('kelas_siswa as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->join('master_kelas as c', 'b.id_kelas', '=', 'c.id_kelas')
            ->where('a.username', $username)
            ->where('b.id_tp', $tp)
            ->where('b.id_smt', $smt)
            ->first();
    }

    /**
     * Get data guru by user ID
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function getDataGuruByUserId(int $userId, int $tp, int $smt): ?object
    {
        return DB::table('master_guru as a')
            ->select('a.*')
            ->where('a.id_user', $userId)
            ->first();
    }

    /**
     * Get data panitia by user ID
     * Tabel master_panitia tidak ada di database (fitur panitia tidak dipakai);
     * kembalikan null bila tabelnya tidak ada supaya halaman /usersiswa tidak
     * error untuk pengguna berperan panitia.
     */
    public function getDataPanitiaByUserId(int $userId, int $tp, int $smt): ?object
    {
        if (! Schema::hasTable('master_panitia')) {
            return null;
        }

        return DB::table('master_panitia as a')
            ->join('users as b', 'a.username', '=', 'b.username')
            ->where('b.id', $userId)
            ->first();
    }

    /**
     * Get data guru by username including wali_kelas info
     */
    // TODO: tabel master_guru, jabatan_guru, master_kelas, level_kelas belum ada migrasinya
    public function getDataGuruByUsername(string $username, int $tp, int $smt): ?object
    {
        return DB::table('master_guru as a')
            ->select('a.*', 'a.nama_guru as nama_lengkap', 'b.id_jabatan', 'b.id_kelas as wali_kelas', 'f.level_id', 'g.level as jabatan')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', $tp)
                    ->where('b.id_smt', '=', $smt);
            })
            ->leftJoin('master_kelas as f', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'f.id_kelas')
                    ->where('f.id_tp', '=', $tp)
                    ->where('f.id_smt', '=', $smt);
            })
            ->leftJoin('level_kelas as g', 'f.level_id', '=', 'g.id_level')
            ->where('a.username', $username)
            ->first();
    }

    /**
     * Get data guru by ID including wali_kelas info
     */
    // TODO: tabel master_guru, jabatan_guru, master_kelas, level_kelas belum ada migrasinya
    public function getDataGuruById(int $id_guru, int $tp, int $smt): ?object
    {
        return DB::table('master_guru as a')
            ->select('a.*', 'a.nama_guru as nama_lengkap', 'b.id_jabatan', 'b.id_kelas as wali_kelas', 'f.level_id', 'g.level as jabatan')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', $tp)
                    ->where('b.id_smt', '=', $smt);
            })
            ->leftJoin('master_kelas as f', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'f.id_kelas')
                    ->where('f.id_tp', '=', $tp)
                    ->where('f.id_smt', '=', $smt);
            })
            ->leftJoin('level_kelas as g', 'f.level_id', '=', 'g.id_level')
            ->where('a.id_guru', $id_guru)
            ->first();
    }

    /**
     * Get running text/announcements
     */
    // TODO: tabel running_text belum ada migrasinya
    public function getRunningText(): array
    {
        // Check if running_text table exists
        if (! Schema::hasTable('running_text')) {
            return [];
        }

        $result = DB::table('running_text')
            ->get()
            ->all();

        return $result ?? [];
    }

    /**
     * Update any table (Multi or Single)
     */
    public function updateData(string $table, $data, string $pk, $id = null, bool $batch = false): bool
    {
        if ($batch) {
            // Laravel tidak punya padanan updateBatch() CI4; tiap baris
            // di-update satu per satu berdasarkan kunci primernya.
            foreach ($data as $row) {
                DB::table($table)->where($pk, $row[$pk] ?? null)->update($row);
            }

            return true;
        }

        // CI4 update() mengembalikan bool sukses; Laravel mengembalikan
        // jumlah baris — query yang berjalan tanpa exception dianggap
        // sukses seperti CI4.
        DB::table($table)->where($pk, $id)->update($data);

        return true;
    }

    /**
     * Delete from any table
     */
    public function hapus(string $table, $ids, string $pk): bool
    {
        // CI4 delete() mengembalikan bool sukses; Laravel mengembalikan
        // jumlah baris — query yang berjalan tanpa exception dianggap
        // sukses seperti CI4.
        if (is_array($ids)) {
            DB::table($table)->whereIn($pk, $ids)->delete();
        } else {
            DB::table($table)->where($pk, $ids)->delete();
        }

        return true;
    }

    /**
     * Get Data Tahun for Datatable
     */
    // TODO: tabel master_tp belum ada migrasinya
    public function getDataTahun(): array
    {
        return DB::table('master_tp')
            ->orderBy('id_tp', 'ASC')
            ->get()
            ->all();
    }

    // TODO: tabel master_tp belum ada migrasinya
    public function getTahunByTahun($tahun)
    {
        return DB::table('master_tp')
            ->where('tahun', $tahun)
            ->first();
    }

    // TODO: tabel master_tp belum ada migrasinya (melalui getTahunByTahun)
    public function getTpBefore($tahun)
    {
        $thns = explode('/', $tahun ?? '');
        if (count($thns) < 2) {
            return null;
        }
        $thn_before = ($thns[0] - 1).'/'.$thns[0];

        return $this->getTahunByTahun($thn_before);
    }

    /**
     * Get setting rapor
     */
    // TODO: tabel setting_rapor belum ada migrasinya
    public function getSettingRapor()
    {
        if (! Schema::hasTable('setting_rapor')) {
            return null;
        }

        return DB::table('setting_rapor')->first();
    }
}

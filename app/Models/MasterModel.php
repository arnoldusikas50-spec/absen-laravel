<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/MasterModel.php
 * Tabel: master_siswa
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (master_siswa).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class MasterModel extends Model
{
    protected $table = 'master_siswa';

    protected $fillable = [
    ];

    public $timestamps = false;

    // ==========================================
    // SISWA FUNCTIONS
    // ==========================================

    /**
     * Get all siswa with pagination
     * Returns students including those not yet assigned to a class
     */
    // TODO: tabel master_siswa, kelas_siswa, master_kelas, buku_induk belum ada migrasinya
    public function getAllSiswa(int $tp, int $smt, int $limit = 10, int $offset = 0, ?string $search = null): array
    {
        $builder = DB::table('master_siswa as a')
            ->select('a.*', 'b.id_kelas', 'c.nama_kelas', 'c.kode_kelas')
            // Use LEFT JOIN with tp/smt in JOIN condition (not WHERE) to include unassigned students
            ->leftJoin('kelas_siswa as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_siswa', '=', 'b.id_siswa')
                    ->where('b.id_tp', '=', (int) $tp)
                    ->where('b.id_smt', '=', (int) $smt);
            })
            ->leftJoin('master_kelas as c', 'b.id_kelas', '=', 'c.id_kelas')
            ->join('buku_induk as bi', function ($join) {
                $join->on('bi.id_siswa', '=', 'a.id_siswa')
                    ->where('bi.status', '=', '1');
            }); // Only active students

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama', 'like', "%{$search}%")
                    ->orWhere('a.nis', 'like', "%{$search}%")
                    ->orWhere('a.nisn', 'like', "%{$search}%");
            });
        }

        return $builder->orderBy('a.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->all();
    }

    /**
     * Count total siswa
     */
    // TODO: tabel master_siswa, kelas_siswa belum ada migrasinya
    public function getTotalSiswa(int $tp, int $smt, ?string $search = null): int
    {
        $builder = DB::table('master_siswa as a')
            ->leftJoin('kelas_siswa as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('b.id_tp', $tp)
            ->where('b.id_smt', $smt);

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama', 'like', "%{$search}%")
                    ->orWhere('a.nis', 'like', "%{$search}%")
                    ->orWhere('a.nisn', 'like', "%{$search}%");
            });
        }

        return $builder->count();
    }

    /**
     * Get siswa by ID
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function getSiswa(int $id): ?object
    {
        return DB::table('master_siswa')
            ->where('id_siswa', $id)
            ->first();
    }

    /**
     * Get siswa by username
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function getSiswaByUsername(string $username): ?object
    {
        return DB::table('master_siswa')
            ->where('username', $username)
            ->first();
    }

    /**
     * Save siswa
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function saveSiswa(array $data): bool
    {
        return DB::table('master_siswa')->insert($data);
    }

    /**
     * Update siswa
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function updateSiswa(int $id, array $data): bool
    {
        DB::table('master_siswa')
            ->where('id_siswa', $id)
            ->update($data);

        return true;
    }

    /**
     * Delete siswa
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function deleteSiswa(int $id): bool
    {
        DB::table('master_siswa')
            ->where('id_siswa', $id)
            ->delete();

        return true;
    }

    /**
     * Get siswa by ID including buku_induk status
     */
    // TODO: tabel master_siswa, buku_induk belum ada migrasinya
    public function getSiswaById(int $id): ?object
    {
        return DB::table('master_siswa as a')
            ->select('a.*', 'b.status')
            ->leftJoin('buku_induk as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('a.id_siswa', $id)
            ->first();
    }

    // TODO: tabel master_guru belum ada migrasinya
    public function getAllGuru()
    {
        return DB::table('master_guru')->orderBy('nama_guru', 'ASC')->get()->all();
    }

    /**
     * Builder dasar daftar siswa: satu baris per siswa pada TP/SMT aktif.
     *
     * Status mengikuti buku_induk (1 aktif, 2 lulus, 3 pindah, 4 keluar);
     * status "5" adalah akun login nonaktif untuk siswa yang masih aktif.
     * Kelas difilter terpisah supaya pengguna bisa memilih status dan kelas
     * dalam waktu yang sama.
     */
    // TODO: tabel master_siswa, buku_induk, kelas_siswa, master_kelas, users belum ada migrasinya
    private function siswaListBuilder(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false)
    {
        $builder = DB::table('master_siswa as a')
            ->leftJoin('buku_induk as u', 'u.id_siswa', '=', 'a.id_siswa')
            ->leftJoin('kelas_siswa as d', function ($join) use ($tp, $smt) {
                $join->on('d.id_siswa', '=', 'a.id_siswa')
                    ->where('d.id_tp', '=', (int) $tp)
                    ->where('d.id_smt', '=', (int) $smt);
            })
            ->leftJoin('master_kelas as f', 'f.id_kelas', '=', 'd.id_kelas')
            ->leftJoin('users as us', 'us.username', '=', 'a.username');

        switch ($status) {
            case '1':
                $builder->where('u.status', '1');
                break;
            case '2':
                $builder->where('u.status', '2');
                break;
            case '3':
                $builder->where('u.status', '3');
                break;
            case '4':
                $builder->where('u.status', '4');
                break;
            case '5':
                $builder->where('u.status', '1')->where('us.active', '0');
                break;
        }

        if ($kelas !== null && $kelas > 0) {
            $builder->where('d.id_kelas', $kelas);
        } elseif ($tanpaKelas) {
            // Filter "Tanpa Kelas": kelas_siswa untuk TP/SMT aktif belum
            // menempatkan siswa ini ke kelas mana pun. Mutually exclusive
            // dengan filter kelas — controller memastikan salah satu saja.
            $builder->whereNull('d.id_kelas');
        }

        if ($search !== null && $search !== '') {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama', 'like', "%{$search}%")
                    ->orWhere('a.nis', 'like', "%{$search}%")
                    ->orWhere('a.nisn', 'like', "%{$search}%");
            });
        }

        return $builder;
    }

    /**
     * Get students with custom pagination and filtering.
     */
    // TODO: tabel master_siswa, buku_induk, kelas_siswa, master_kelas, users belum ada migrasinya
    public function getSiswaPage(int $tp, int $smt, int $offset, int $limit, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): array
    {
        $builder = $this->siswaListBuilder($tp, $smt, $status, $kelas, $search, $tanpaKelas);

        return $builder
            ->select('a.id_siswa', 'a.foto', 'a.nama', 'a.nis', 'a.nisn', 'a.jenis_kelamin', 'a.agama', 'a.username', 'd.id_kelas', 'f.nama_kelas', 'us.active', 'u.status')
            ->orderBy('a.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->all();
    }

    /**
     * Count total students with filtering.
     */
    // TODO: tabel master_siswa, buku_induk, kelas_siswa, master_kelas, users belum ada migrasinya
    public function getSiswaTotalPage(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): int
    {
        return $this->siswaListBuilder($tp, $smt, $status, $kelas, $search, $tanpaKelas)->count();
    }

    /**
     * Ringkasan jumlah siswa untuk kartu statistik halaman /datasiswa.
     *
     * @return array<string,int>
     */
    // TODO: tabel master_siswa, buku_induk, kelas_siswa, users belum ada migrasinya
    public function getSiswaStats(int $tp, int $smt): array
    {
        $rows = DB::select(
            "SELECT
                (SELECT COUNT(*) FROM master_siswa) AS total,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '1') AS aktif,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '2') AS lulus,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '3') AS pindah,
                (SELECT COUNT(DISTINCT id_siswa) FROM buku_induk WHERE status = '4') AS keluar,
                (SELECT COUNT(DISTINCT a.id_siswa) FROM master_siswa a
                    LEFT JOIN kelas_siswa d ON d.id_siswa = a.id_siswa AND d.id_tp = ? AND d.id_smt = ?
                    WHERE d.id_kelas IS NULL) AS tanpa_kelas,
                (SELECT COUNT(DISTINCT a.id_siswa) FROM master_siswa a
                    INNER JOIN users us ON us.username = a.username
                    WHERE us.active = '0') AS akun_nonaktif",
            [$tp, $smt]
        );
        $row = $rows[0] ?? null;

        return [
            'total' => (int) ($row->total ?? 0),
            'aktif' => (int) ($row->aktif ?? 0),
            'lulus' => (int) ($row->lulus ?? 0),
            'pindah' => (int) ($row->pindah ?? 0),
            'keluar' => (int) ($row->keluar ?? 0),
            'tanpa_kelas' => (int) ($row->tanpa_kelas ?? 0),
            'akun_nonaktif' => (int) ($row->akun_nonaktif ?? 0),
        ];
    }

    /**
     * Get students by array of IDs
     */
    // TODO: tabel master_siswa, kelas_siswa, master_kelas belum ada migrasinya
    public function getSiswaByArrId(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $result = DB::table('master_siswa as a')
            ->select('a.id_siswa', 'a.nama', 'f.nama_kelas')
            ->leftJoin('kelas_siswa as b', 'b.id_siswa', '=', 'a.id_siswa')
            ->leftJoin('master_kelas as f', 'f.id_kelas', '=', 'b.id_kelas')
            ->whereIn('a.id_siswa', $ids)
            ->get()
            ->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }

        return $ret;
    }

    /**
     * Get students by array of NISN, NIS, or Username
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function getSiswaByArrNisn(array $arr_nisn, array $arr_nis, array $arr_username): array
    {
        return DB::table('master_siswa')
            ->select('id_siswa', 'nama', 'nisn', 'nis', 'username')
            ->whereIn('nisn', $arr_nisn)
            ->orWhereIn('nis', $arr_nis)
            ->orWhereIn('username', $arr_username)
            ->get()
            ->all();
    }

    /**
     * Get students by list of usernames
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function getSiswaByUsernames(array $usernames): array
    {
        if (empty($usernames)) {
            return [];
        }

        return DB::table('master_siswa')
            ->select('id_siswa', 'username')
            ->whereIn('username', $usernames)
            ->get()
            ->all();
    }

    /**
     * Get students for new class assignment
     */
    // TODO: tabel kelas_siswa, master_siswa, master_kelas belum ada migrasinya
    public function getSiswaKelasBaru(int $tp, int $smt): array
    {
        $result = DB::table('kelas_siswa as a')
            ->select('b.id_siswa', 'b.nama', 'f.id_kelas', 'f.nama_kelas', 'f.kode_kelas')
            ->join('master_siswa as b', 'b.id_siswa', '=', 'a.id_siswa')
            ->join('master_kelas as f', 'f.id_kelas', '=', 'a.id_kelas')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->get()
            ->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }

        return $ret;
    }

    /**
     * Get data siswa by kelas with optional pagination and search
     */
    // TODO: tabel kelas_siswa, master_siswa, master_kelas, users belum ada migrasinya
    public function getDataSiswaByKelas(int $tp, int $smt, int $id_kelas, int $offset = 0, int $limit = 0, ?string $search = null): array
    {
        $builder = DB::table('kelas_siswa as a')
            ->select('b.id_siswa', 'b.nama', 'b.nis', 'b.nisn', 'b.jenis_kelamin', 'b.username', 'b.password', 'b.foto', 'b.agama', 'f.nama_kelas', 'us.active')
            ->rightJoin('master_siswa as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->join('master_kelas as f', 'a.id_kelas', '=', 'f.id_kelas')
            ->leftJoin('users as us', 'b.username', '=', 'us.username')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('a.id_kelas', $id_kelas);

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('b.nama', 'like', "%{$search}%")
                    ->orWhere('b.nis', 'like', "%{$search}%")
                    ->orWhere('b.nisn', 'like', "%{$search}%");
            });
        }

        return $builder->orderBy('b.nama', 'ASC')->get()->all();
    }

    /**
     * Count data siswa by kelas for pagination
     */
    // TODO: tabel kelas_siswa, master_siswa belum ada migrasinya
    public function getDataSiswaByKelasPage(int $tp, int $smt, int $id_kelas, ?string $search = null): int
    {
        $builder = DB::table('kelas_siswa as a')
            ->join('master_siswa as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->where('a.id_kelas', $id_kelas);

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('b.nama', 'like', "%{$search}%")
                    ->orWhere('b.nis', 'like', "%{$search}%")
                    ->orWhere('b.nisn', 'like', "%{$search}%");
            });
        }

        return $builder->count();
    }

    /**
     * Alias for getDataSiswaByKelas for backward compatibility
     */
    // TODO: tabel kelas_siswa, master_siswa, master_kelas, users belum ada migrasinya
    public function getSiswaByKelas(int $id_kelas, int $tp, int $smt): array
    {
        return $this->getDataSiswaByKelas($tp, $smt, $id_kelas);
    }

    // ==========================================
    // GURU FUNCTIONS
    // ==========================================

    /**
     * Get all guru with full details (Ported from CI3)
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas, master_tp, master_smt, users belum ada migrasinya
    public function getAllDataGuru(int $tp, int $smt): array
    {
        return DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'a.nip', 'a.kode_guru', 'a.jenis_kelamin', 'a.foto', 'b.id_jabatan', 'b.id_kelas', 'b.mapel_kelas', 'b.ekstra_kelas', 'c.id_level', 'c.level', 'd.nama_kelas', 'e.tahun', 'f.nama_smt', 'g.active')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', (int) $tp)
                    ->where('b.id_smt', '=', (int) $smt);
            })
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level')
            ->leftJoin('master_kelas as d', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'd.id_kelas')
                    ->where('d.id_tp', '=', (int) $tp)
                    ->where('d.id_smt', '=', (int) $smt);
            })
            ->leftJoin('master_tp as e', 'b.id_tp', '=', 'e.id_tp')
            ->leftJoin('master_smt as f', 'b.id_smt', '=', 'f.id_smt')
            ->leftJoin('users as g', 'a.username', '=', 'g.username')
            ->orderBy('c.id_level', 'DESC')
            ->orderBy('a.id_guru', 'ASC')
            ->get()->all();
    }

    /**
     * Get data guru for DataTables (Ported from CI3)
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas, master_tp, master_smt belum ada migrasinya
    public function getDataGuru(int $tp, int $smt, ?string $search = null): array
    {
        $builder = DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'a.nip', 'a.kode_guru', 'a.jenis_kelamin', 'a.foto', 'b.id_jabatan', 'b.id_kelas', 'b.mapel_kelas', 'c.id_level', 'c.level', 'd.nama_kelas', 'e.tahun', 'f.nama_smt')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', (int) $tp)
                    ->where('b.id_smt', '=', (int) $smt);
            })
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level')
            ->leftJoin('master_kelas as d', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'd.id_kelas')
                    ->where('d.id_tp', '=', (int) $tp)
                    ->where('d.id_smt', '=', (int) $smt);
            })
            ->leftJoin('master_tp as e', 'b.id_tp', '=', 'e.id_tp')
            ->leftJoin('master_smt as f', 'b.id_smt', '=', 'f.id_smt');

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama_guru', 'like', "%{$search}%")
                    ->orWhere('a.nip', 'like', "%{$search}%")
                    ->orWhere('a.kode_guru', 'like', "%{$search}%");
            });
        }

        return $builder->get()->all();
    }

    /**
     * Get guru by ID including jabatan (Ported from CI3)
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas belum ada migrasinya
    public function getGuruById(int $id, ?int $id_tp = null, ?int $id_smt = null): ?object
    {
        $builder = DB::table('master_guru as a')
            ->select('a.*', 'b.id_jabatan', 'b.id_kelas', 'b.mapel_kelas', 'b.ekstra_kelas', 'c.*')
            ->leftJoin('jabatan_guru as b', 'a.id_guru', '=', 'b.id_guru')
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level');

        if ($id_tp !== null && $id_smt !== null) {
            // CI4 select() di sini menambah kolom; Laravel select() menimpa,
            // jadi dipakai addSelect().
            $builder->addSelect('d.*')
                ->leftJoin('master_kelas as d', function ($join) use ($id_tp, $id_smt) {
                    $join->on('a.id_guru', '=', 'd.guru_id')
                        ->where('d.id_tp', '=', (int) $id_tp)
                        ->where('d.id_smt', '=', (int) $id_smt);
                });
        }

        return $builder->where('a.id_guru', $id)->first();
    }

    /**
     * Riwayat jabatan guru, terurut dari TP/SMT terbaru.
     * Return flat list agar setiap periode dapat ditampilkan apa adanya.
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas, master_tp, master_smt belum ada migrasinya
    public function getDetailJabatanGuru(int $id_guru): array
    {
        return DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'b.id_tp', 'b.id_smt', 'b.mapel_kelas', 'b.ekstra_kelas', 'c.id_level', 'c.level', 'd.id_kelas', 'd.nama_kelas', 'e.tahun', 'f.smt', 'f.nama_smt')
            ->join('jabatan_guru as b', 'a.id_guru', '=', 'b.id_guru')
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level')
            ->leftJoin('master_kelas as d', 'b.id_kelas', '=', 'd.id_kelas')
            ->leftJoin('master_tp as e', 'b.id_tp', '=', 'e.id_tp')
            ->leftJoin('master_smt as f', 'b.id_smt', '=', 'f.id_smt')
            ->where('a.id_guru', $id_guru)
            ->orderBy('b.id_tp', 'DESC')
            ->orderBy('b.id_smt', 'ASC')
            ->get()->all();
    }

    /**
     * Jabatan guru for specific TP/SMT (Ported from CI3)
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas belum ada migrasinya
    public function getJabatanGuru(int $id_guru, int $tp, int $smt): ?object
    {
        return DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'b.mapel_kelas', 'b.ekstra_kelas', 'c.id_level', 'c.level', 'd.id_kelas', 'd.nama_kelas')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', (int) $tp)
                    ->where('b.id_smt', '=', (int) $smt);
            })
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level')
            ->leftJoin('master_kelas as d', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'd.id_kelas')
                    ->where('d.id_tp', '=', (int) $tp)
                    ->where('d.id_smt', '=', (int) $smt);
            })
            ->where('a.id_guru', $id_guru)
            ->first();
    }

    /**
     * Class/Mapel assignment for guru (Ported from CI3)
     */
    // TODO: tabel jabatan_guru, master_guru belum ada migrasinya
    public function getGuruMapel(int $tp, int $smt, ?array $ids_guru = null): array
    {
        $builder = DB::table('jabatan_guru as a')
            ->select('a.mapel_kelas', 'a.ekstra_kelas', 'a.id_jabatan', 'a.id_kelas', 'b.id_guru', 'b.nama_guru')
            ->join('master_guru as b', 'a.id_guru', '=', 'b.id_guru')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt);

        if ($ids_guru) {
            $builder->whereIn('a.id_guru', $ids_guru);
        }

        return $builder->get()->all();
    }

    /**
     * Update guru by ID
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function updateGuru(int $id, array $data): bool
    {
        DB::table('master_guru')
            ->where('id_guru', $id)
            ->update($data);

        return true;
    }

    /**
     * Delete guru by ID
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function deleteGuru($id): bool
    {
        if (is_array($id)) {
            DB::table('master_guru')->whereIn('id_guru', $id)->delete();
        } else {
            DB::table('master_guru')->where('id_guru', $id)->delete();
        }

        return true;
    }

    /**
     * Delete teacher from linked tables.
     * Dijalankan dalam satu transaksi agar tidak ada penghapusan setengah jadi.
     */
    // TODO: tabel master_guru, jabatan_guru, kelas_materi, kelas_catatan_mapel, guru_jurnal, post, post_comments, post_reply, master_kelas, users, users_groups belum ada migrasinya
    public function deleteGuruLinked(int $id_guru): bool
    {
        $tables = [
            'jabatan_guru' => 'id_guru',
            'kelas_materi' => 'id_guru',
            'kelas_catatan_mapel' => 'id_guru',
            'guru_jurnal' => 'id_guru',
            'post' => 'dari',
            'post_comments' => 'dari',
            'post_reply' => 'dari',
        ];

        DB::beginTransaction();

        try {
            $guru = DB::table('master_guru')
                ->select('id_user')
                ->where('id_guru', $id_guru)
                ->first();

            // Lepas referensi wali kelas supaya tidak menunjuk guru yang sudah dihapus.
            if (Schema::hasTable('master_kelas') && Schema::hasColumn('master_kelas', 'guru_id')) {
                DB::table('master_kelas')
                    ->where('guru_id', $id_guru)
                    ->update(['guru_id' => null]);
            }

            foreach ($tables as $table => $field) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                // CI4 delete() mengembalikan bool sukses query; Laravel
                // mengembalikan jumlah baris (0 bila tidak ada baris cocok),
                // jadi hasilnya tidak dicek — kegagalan query melempar
                // exception dan ditangkap di bawah.
                DB::table($table)->where($field, $id_guru)->delete();
            }

            $idUser = (int) ($guru->id_user ?? 0);
            if ($idUser > 0 && Schema::hasTable('users')) {
                $isAdmin = Schema::hasTable('users_groups')
                    && DB::table('users_groups')
                        ->where('user_id', $idUser)
                        ->where('group_id', 1)
                        ->count() > 0;

                if (! $isAdmin) {
                    // Akun tidak ikut dihapus agar jejak/relasi lain tetap utuh,
                    // tetapi dinonaktifkan supaya tidak bisa login lagi.
                    DB::table('users')->where('id', $idUser)->update(['active' => 0]);
                }
            }

            if (! $this->deleteGuru($id_guru)) {
                throw new \RuntimeException('Gagal menghapus data master_guru');
            }

            DB::commit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[deleteGuruLinked] guru id='.$id_guru.': '.$e->getMessage());

            return false;
        }
    }

    // ==========================================
    // KELAS FUNCTIONS
    // ==========================================

    /**
     * Get all kelas by TP and semester
     */
    // TODO: tabel master_kelas, master_guru belum ada migrasinya
    public function getAllKelas(int $tp, int $smt): array
    {
        return DB::table('master_kelas as a')
            ->select('a.*', 'b.nama_guru as wali')
            ->leftJoin('master_guru as b', 'a.guru_id', '=', 'b.id_guru')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('a.level_id', 'ASC')
            ->orderBy('a.nama_kelas', 'ASC')
            ->get()
            ->all();
    }

    /**
     * Get kelas by ID
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function getKelasById(int $id): ?object
    {
        return DB::table('master_kelas')
            ->select('id_kelas', 'nama_kelas', 'level_id', 'jumlah_siswa')
            ->where('id_kelas', $id)
            ->first();
    }

    /**
     * Get kelas by level
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function getKelasByLevel(int $tp, int $smt, int $level): array
    {
        return DB::table('master_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('level_id', $level)
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->all();
    }

    /**
     * Save kelas
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function saveKelas(array $data): bool
    {
        return DB::table('master_kelas')->insert($data);
    }

    /**
     * Update kelas
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function updateKelas(int $id, array $data): bool
    {
        DB::table('master_kelas')
            ->where('id_kelas', $id)
            ->update($data);

        return true;
    }

    /**
     * Delete kelas
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function deleteKelas(int $id): bool
    {
        DB::table('master_kelas')
            ->where('id_kelas', $id)
            ->delete();

        return true;
    }

    /**
     * Count siswa in kelas
     */
    // TODO: tabel kelas_siswa belum ada migrasinya
    public function countSiswaInKelas(int $idKelas, int $tp, int $smt): int
    {
        return DB::table('kelas_siswa')
            ->where('id_kelas', $idKelas)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->count();
    }

    // ==========================================
    // MAPEL FUNCTIONS
    // ==========================================

    /**
     * Get all mapel
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function getAllMapel(?int $jenjang = null): array
    {
        $builder = DB::table('master_mapel');

        if ($jenjang !== null) {
            $builder->where('jenjang', $jenjang);
        }

        return $builder->orderBy('urutan_tampil', 'ASC')
            ->orderBy('nama_mapel', 'ASC')
            ->get()
            ->all();
    }

    // TODO: tabel master_kelompok_mapel belum ada migrasinya
    public function getDataKelompokMapel()
    {
        $result = DB::table('master_kelompok_mapel')
            ->where('id_parent', '0')
            ->orderBy('kode_kel_mapel')
            ->get()
            ->all();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kel_mapel] = $row;
        }

        return $ret;
    }

    // TODO: tabel master_kelompok_mapel belum ada migrasinya
    public function getDataSubKelompokMapel()
    {
        $result = DB::table('master_kelompok_mapel')
            ->where('id_parent', '<>', 0)
            ->orderBy('kode_kel_mapel')
            ->get()
            ->all();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kel_mapel] = $row;
        }

        return $ret;
    }

    // TODO: tabel master_kelompok_mapel belum ada migrasinya
    public function getKategoriKelompokMapel()
    {
        return DB::table('master_kelompok_mapel')
            ->select('kode_kel_mapel', 'kategori')
            ->where(function ($q) {
                $q->where('kategori', 'WAJIB')
                    ->orWhere('kategori', 'AGAMA (Kemenag)');
            })
            ->get()
            ->all();
    }

    /**
     * Get mapel by ID
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function getMapel(int $id): ?object
    {
        return DB::table('master_mapel')
            ->where('id_mapel', $id)
            ->first();
    }

    /**
     * Save mapel
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function saveMapel(array $data): bool
    {
        return DB::table('master_mapel')->insert($data);
    }

    /**
     * Update mapel
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function updateMapel(int $id, array $data): bool
    {
        DB::table('master_mapel')
            ->where('id_mapel', $id)
            ->update($data);

        return true;
    }

    /**
     * Delete mapel
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function deleteMapel(int $id): bool
    {
        DB::table('master_mapel')
            ->where('id_mapel', $id)
            ->delete();

        return true;
    }

    // ==========================================
    // JURUSAN/PROGRAM FUNCTIONS
    // ==========================================

    /**
     * Get all jurusan
     */
    // TODO: tabel master_jurusan, jurusan belum ada migrasinya
    public function getAllJurusan(): array
    {
        // Prefer master_jurusan if exists and has data, otherwise fallback to jurusan (legacy)
        if (Schema::hasTable('master_jurusan')) {
            $rows = DB::table('master_jurusan')->orderBy('nama_jurusan', 'ASC')->get()->all();
            $normalized = [];
            foreach ($rows as $r) {
                $r->singkatan = $r->kode_jurusan ?? '';
                $normalized[] = $r;
            }
            if (count($normalized) > 0) {
                return $normalized;
            }
        }
        // Fallback legacy table (column jurusan_id -> id_jurusan)
        if (Schema::hasTable('jurusan')) {
            $rows = DB::table('jurusan')->orderBy('nama_jurusan', 'ASC')->get()->all();
            $normalized = [];
            foreach ($rows as $r) {
                $obj = (object) [
                    'id_jurusan' => $r->jurusan_id ?? ($r->id_jurusan ?? null),
                    'nama_jurusan' => $r->nama_jurusan ?? '',
                    'singkatan' => $r->singkatan ?? '',
                ];
                $normalized[] = $obj;
            }

            return $normalized;
        }

        return [];
    }

    /**
     * Get jurusan by ID
     */
    // TODO: tabel master_jurusan belum ada migrasinya
    public function getJurusan(int $id): ?object
    {
        return DB::table('master_jurusan')
            ->where('id_jurusan', $id)
            ->first();
    }

    // ==========================================
    // DROPDOWN HELPERS
    // ==========================================

    /**
     * Get kelas for dropdown
     */
    // TODO: tabel master_kelas, master_guru belum ada migrasinya
    public function getDropdownKelas(int $tp, int $smt): array
    {
        $result = [];
        $kelas = $this->getAllKelas($tp, $smt);

        foreach ($kelas as $k) {
            $result[$k->id_kelas] = $k->nama_kelas;
        }

        return $result;
    }

    /**
     * Get mapel for dropdown
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function getDropdownMapel(): array
    {
        $result = [];
        $mapels = $this->getAllMapel();

        foreach ($mapels as $m) {
            $result[$m->id_mapel] = $m->nama_mapel;
        }

        return $result;
    }

    /**
     * Get guru for dropdown
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function getDropdownGuru(): array
    {
        $result = [];
        $gurus = $this->getAllGuru();

        foreach ($gurus as $g) {
            $result[$g->id_guru] = $g->nama_guru;
        }

        return $result;
    }

    /**
     * Generate id_kelas_siswa (kelas_siswa tidak memakai auto_increment,
     * jadi PK selalu dihitung eksplisit — lihat juga KelasSiswaUnique migration).
     */
    // TODO: tabel kelas_siswa belum ada migrasinya
    public function generateKelasSiswaId(): int
    {
        return (int) (DB::table('kelas_siswa')->max('id_kelas_siswa') ?? 0) + 1;
    }

    /**
     * Create record in any table
     *
     * NOTE: nama ini membayangi (shadow) Eloquent Model::create(array $attributes = []).
     * Tanda tangan CI4 persis (string $table, array $data) tidak bisa dipakai apa adanya:
     * method induk adalah static dengan parameter pertama array, dan method non-static
     * tidak boleh menimpa method static — keduanya fatal error saat class dimuat.
     * Karena itu method ini tetap static (seperti induk) dengan parameter pertama
     * yang diperlebar menjadi array|string agar kompatibel dengan induk, dan
     * pemanggilan gaya CI4 lewat instance ($this->masterModel->create('tabel', $data),
     * 5 call site: Dataguru, Datakelas, Datasiswa) tetap jalan — PHP mengizinkan
     * pemanggilan method static lewat instance. Bila dipanggil gaya Eloquent
     * dengan satu array (tanpa call site saat ini), data di-insert ke tabel
     * model ini (master_siswa). Mengembalikan bool seperti CI4 insert(),
     * bukan instance Model seperti Eloquent create(); DatakelasController
     * mengandalkan perbandingan `=== false`.
     */
    public static function create(array|string $attributes = [], array $data = []): bool
    {
        if (is_string($attributes)) {
            return DB::table($attributes)->insert($data);
        }

        return DB::table((new static)->getTable())->insert($attributes);
    }

    /**
     * Create multiple records
     */
    public function insertBatchData(string $table, array $data)
    {
        if (empty($data)) {
            return false;
        }

        DB::table($table)->insert($data);

        return count($data);
    }

    /**
     * Update record in any table
     */
    public function updateData(string $table, array $data, string $pk, $id)
    {
        DB::table($table)->where($pk, $id)->update($data);

        return true;
    }

    /**
     * Update multiple records
     */
    public function updateBatchData(string $table, array $data, string $pk)
    {
        if (empty($data)) {
            return false;
        }

        try {
            $affected = 0;
            foreach ($data as $row) {
                if (! isset($row[$pk])) {
                    continue;
                }
                $values = $row;
                unset($values[$pk]);
                $affected += DB::table($table)->where($pk, $row[$pk])->update($values);
            }

            return $affected;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete from any table
     */
    public function hapus(string $table, $ids, string $pk): bool
    {
        if (is_array($ids)) {
            DB::table($table)->whereIn($pk, $ids)->delete();
        } else {
            DB::table($table)->where($pk, $ids)->delete();
        }

        return true;
    }

    /**
     * Get Jml Hari Efektif
     */
    // TODO: tabel master_hari_efektif belum ada migrasinya
    public function getJmlHariEfektif(string $id)
    {
        return DB::table('master_hari_efektif')
            ->where('id_hari_efektif', $id)
            ->first();
    }

    /**
     * Get guru mapel and kelas
     */
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function getGuruMapelKelas(int $idGuru, int $tp, int $smt)
    {
        return DB::table('jabatan_guru')
            ->where('id_guru', $idGuru)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->first();
    }

    /**
     * Get all kelas by array of IDs
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function getAllKelasByArrayId(int $tp, int $smt, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return DB::table('master_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->whereIn('id_kelas', $ids)
            ->orderBy('level_id', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->all();
    }

    /**
     * Get levels by jenjang
     */
    public function getLevelsByJenjang(int $jenjang): array
    {
        $levels = [];
        if ($jenjang == 1) { // SD
            $levels = [
                (object) ['level' => 1],
                (object) ['level' => 2],
                (object) ['level' => 3],
                (object) ['level' => 4],
                (object) ['level' => 5],
                (object) ['level' => 6],
            ];
        } elseif ($jenjang == 2) { // SMP
            $levels = [
                (object) ['level' => 7],
                (object) ['level' => 8],
                (object) ['level' => 9],
            ];
        } elseif ($jenjang == 3 || $jenjang == 4) { // SMA/SMK
            $levels = [
                (object) ['level' => 10],
                (object) ['level' => 11],
                (object) ['level' => 12],
            ];
        } else {
            // Default fallback if unknown jenjang, return empty or common levels?
            // Returning empty array as per CI3 logic
        }

        return $levels;
    }

    /**
     * Get unique religions from master_siswa
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function getAgamaSiswa(): array
    {
        $result = DB::table('master_siswa')
            ->select('agama')
            ->distinct()
            ->whereNotNull('agama')
            ->where('agama', '!=', '0')
            ->where('agama', 'not like', '%Pilih%')
            ->get()
            ->all();

        $ret = ['-' => 'Bukan Mapel Agama'];
        foreach ($result as $row) {
            $ret[$row->agama] = $row->agama;
        }

        return $ret;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getAllKodeKelas($tp, $smt)
    {
        $result = DB::table('master_kelas')
            ->select('id_kelas', 'kode_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()
            ->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->kode_kelas;
        }

        return $ret;
    }

    // ==========================================
    // GURU/JABATAN FUNCTIONS (additional)
    // ==========================================
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function getAllJabatanGuru(int $id_guru): array
    {
        $result = DB::table('jabatan_guru')
            ->where('id_guru', $id_guru)
            ->get()->all();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_tp][$row->id_smt] = $row->id_kelas;
        }

        return $ret;
    }

    // ==========================================
    // MAPEL/Kelompok FUNCTIONS (additional)
    // ==========================================
    // TODO: tabel master_kelompok_mapel belum ada migrasinya
    public function getKodeKelompokMapel(): array
    {
        $result = DB::table('master_kelompok_mapel')
            ->orderBy('kode_kel_mapel')
            ->get()->all();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kel_mapel] = $row;
        }

        return $ret;
    }

    // TODO: tabel master_mapel belum ada migrasinya
    public function getAllStatusMapel(?array $arrKelompok = null, ?string $arrMapel = null): array
    {
        $builder = DB::table('master_mapel');

        if ($arrKelompok !== null) {
            $builder->whereIn('kelompok', $arrKelompok);
        }
        if ($arrMapel !== null) {
            $builder->orWhereIn('id_mapel', explode(',', $arrMapel));
        }

        return $builder->orderBy('urutan_tampil')->get()->all();
    }
}

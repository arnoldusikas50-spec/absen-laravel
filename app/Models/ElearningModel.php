<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/ElearningModel.php
 * Tabel: master_mapel
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (master_mapel).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class ElearningModel extends Model
{
    protected $table = 'master_mapel';

    protected $fillable = [
    ];

    public $timestamps = false;

    // TODO: tabel bulan belum ada migrasinya
    public function getBulan()
    {
        $result = DB::table('bulan')->get()->all();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_bln] = $row->nama_bln;
            }
        }

        return $ret;
    }

    // TODO: tabel master_mapel belum ada migrasinya
    public function getAllKodeMapel()
    {
        $builder = DB::table('master_mapel');
        $builder->orderBy('urutan_tampil');
        $builder->where('status', '1');
        $result = $builder->get()->all();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_mapel] = $row->kode;
            }
        }

        return $ret;
    }

    // TODO: tabel kelas_jadwal_materi, kelas_materi, master_mapel belum ada migrasinya
    public function getMateriSiswa($id_kelas, $tgl, $jenis)
    {
        $builder = DB::table('kelas_jadwal_materi as a');
        $builder->select('a.*', 'b.id_kjm', 'c.nama_mapel', 'c.kode');
        $builder->join('kelas_materi as b', 'a.id_materi', '=', 'b.id_materi');
        $builder->join('master_mapel as c', 'b.id_mapel', '=', 'c.id_mapel');
        $builder->where('a.id_kelas', $id_kelas);
        $builder->where('a.jadwal_materi', $tgl);
        $builder->where('a.jenis', $jenis);
        $result = $builder->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_mapel] = $row;
        }

        return $ret;
    }

    /**
     * Get nilai materi siswa (for student nilai page)
     */
    // TODO: tabel log_materi, kelas_jadwal_materi, kelas_materi, master_mapel belum ada migrasinya
    public function getNilaiMateriSiswa($id_siswa)
    {
        $builder = DB::table('log_materi as a');
        $builder->select('a.nilai', 'a.catatan', 'b.jadwal_materi', 'c.kode_materi', 'c.judul_materi', 'c.jenis', 'd.nama_mapel', 'd.kode');
        $builder->join('kelas_jadwal_materi as b', 'a.id_materi', '=', 'b.id_kjm');
        $builder->join('kelas_materi as c', 'b.id_materi', '=', 'c.id_materi');
        $builder->join('master_mapel as d', 'c.id_mapel', '=', 'd.id_mapel');
        $builder->where('a.id_siswa', $id_siswa);
        $result = $builder->get()->all();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->jenis][] = $row;
            }
        }

        return $ret;
    }

    // =====================================================
    // JADWAL (SCHEDULE) METHODS - Ported from CI3
    // =====================================================

    /**
     * Get all Mapel
     */
    // TODO: tabel master_mapel belum ada migrasinya
    public function getAllMapel()
    {
        return DB::table('master_mapel')
            ->select('id_mapel', 'nama_mapel', 'kode')
            ->orderBy('nama_mapel', 'ASC')
            ->get()->all();
    }

    /**
     * Get Mapel as array [id => nama]
     */
    // TODO: tabel master_mapel belum ada migrasinya (via getAllMapel)
    public function getMapelArray()
    {
        $result = [];
        $mapels = $this->getAllMapel();
        foreach ($mapels as $m) {
            $result[$m->id_mapel] = $m->nama_mapel;
        }

        return $result;
    }

    /**
     * Get all Guru
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function getAllGuru()
    {
        return DB::table('master_guru')
            ->select('id_guru', 'nama_guru', 'nip', 'kode_guru')
            ->orderBy('nama_guru', 'ASC')
            ->get()->all();
    }

    /**
     * Get Guru Mapel Kelas
     */
    // TODO: tabel master_guru, jabatan_guru, level_guru, master_kelas belum ada migrasinya
    public function getGuruMapelKelas($id_guru, $tp, $smt)
    {
        $tp = (int) $tp;
        $smt = (int) $smt;

        return DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'b.mapel_kelas', 'b.ekstra_kelas', 'c.id_level', 'c.level', 'd.id_kelas', 'd.nama_kelas')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', $tp)
                    ->where('b.id_smt', '=', $smt);
            })
            ->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level')
            ->leftJoin('master_kelas as d', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'd.id_kelas')
                    ->where('d.id_tp', '=', $tp)
                    ->where('d.id_smt', '=', $smt);
            })
            ->where('a.id_guru', $id_guru)
            ->first();
    }

    /**
     * Get all Kelas (with optional level filter)
     */
    // TODO: tabel master_kelas, kelas_siswa belum ada migrasinya
    public function getAllKelas($tp = null, $smt = null, $level = null)
    {
        $builder = DB::table('master_kelas as k')
            ->select('k.id_kelas', 'k.nama_kelas', 'k.kode_kelas');

        if ($tp && $smt) {
            $tp = (int) $tp;
            $smt = (int) $smt;
            $builder->join('kelas_siswa as ks', function ($join) use ($tp, $smt) {
                $join->on('ks.id_kelas', '=', 'k.id_kelas')
                    ->where('ks.id_tp', '=', $tp)
                    ->where('ks.id_smt', '=', $smt);
            });
            // MySQL ONLY_FULL_GROUP_BY menolak kolom select yang tidak ada di
            // GROUP BY, jadi semua kolom yang di-select ikut dikelompokkan.
            $builder->groupBy('k.id_kelas', 'k.nama_kelas', 'k.kode_kelas');
        }

        return $builder->orderBy('k.nama_kelas', 'ASC')
            ->get()->all();
    }

    /**
     * Get all Kelas sorted as array [id => nama]
     */
    // TODO: tabel master_kelas, kelas_siswa belum ada migrasinya (via getAllKelas)
    public function getAllKelasSort($tp = null, $smt = null, $level = null)
    {
        $kelas = $this->getAllKelas($tp, $smt, $level);
        $result = [];
        foreach ($kelas as $k) {
            $result[$k->id_kelas] = $k->nama_kelas;
        }

        return $result;
    }

    /**
     * Get Kelas by Array of IDs
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function getAllKelasByArrayId($tp, $smt, $arrId)
    {
        if (empty($arrId)) {
            return [];
        }

        $rows = DB::table('master_kelas as k')
            ->select('k.id_kelas', 'k.nama_kelas')
            ->whereIn('k.id_kelas', $arrId)
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas] = $r->nama_kelas;
        }

        return $result;
    }

    /**
     * Get Jadwal KBM (Class Schedule Settings)
     */
    // TODO: tabel kelas_jadwal_kbm belum ada migrasinya
    public function getJadwalKbm($tp, $smt)
    {
        $rows = DB::table('kelas_jadwal_kbm')
            ->where(['id_tp' => $tp, 'id_smt' => $smt])
            ->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas] = $r;
        }

        return $result;
    }

    /**
     * Decode penugasan mapel-kelas pada jabatan_guru.mapel_kelas.
     * Format: [['id_mapel' => 1, 'nama_mapel' => '...', 'kelas_mapel' => [['kelas' => 20], ...]], ...]
     * Data lama tersimpan sebagai serialize, data baru sebagai JSON.
     */
    public function decodeMapelKelasAssignments($raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = @unserialize($raw, ['allowed_classes' => false]);
        if ($decoded === false && $raw !== 'b:0;') {
            $decoded = json_decode($raw, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Peta penugasan guru per kelas:mapel.
     * Return: ['kelas:mapel' => [id_guru => nama_guru], ...]
     */
    // TODO: tabel jabatan_guru, master_guru belum ada migrasinya
    public function getGuruAssignmentMap(int $idTp, int $idSmt): array
    {
        $rows = DB::table('jabatan_guru as jg')
            ->select('jg.id_guru', 'jg.mapel_kelas', 'g.nama_guru')
            ->leftJoin('master_guru as g', 'g.id_guru', '=', 'jg.id_guru')
            ->where('jg.id_tp', $idTp)
            ->where('jg.id_smt', $idSmt)
            ->get()->all();

        $map = [];
        foreach ($rows as $row) {
            $idGuru = (int) ($row->id_guru ?? 0);
            if ($idGuru <= 0) {
                continue;
            }

            $namaGuru = trim((string) ($row->nama_guru ?? '')) ?: ('Guru #'.$idGuru);
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || ! is_array($kelasList)) {
                    continue;
                }

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas <= 0) {
                        continue;
                    }

                    $map[$idKelas.':'.$idMapel][$idGuru] = $namaGuru;
                }
            }
        }

        return $map;
    }

    /**
     * Daftar pasangan kelas:mapel milik satu guru pada TP/SMT.
     * Return: ['kelas:mapel' => true, ...]
     */
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function getGuruAssignedPairs(int $idGuru, int $idTp, int $idSmt): array
    {
        if ($idGuru <= 0) {
            return [];
        }

        $rows = DB::table('jabatan_guru')
            ->select('mapel_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->all();

        $pairs = [];
        foreach ($rows as $row) {
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || ! is_array($kelasList)) {
                    continue;
                }

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas <= 0) {
                        continue;
                    }
                    $pairs[$idKelas.':'.$idMapel] = true;
                }
            }
        }

        return $pairs;
    }

    /**
     * Pasangan kelas:mapel pada jadwal yang belum diampu guru mana pun.
     * Sesi seperti ini tidak muncul di quick-pick jurnal dan tidak ikut
     * dihitung gerbang wajib jurnal sebelum pulang.
     *
     * @return array<int,object>
     */
    // TODO: tabel kelas_jadwal_mapel, master_kelas, master_mapel, jabatan_guru belum ada migrasinya
    public function getUnassignedSchedulePairs(int $idTp, int $idSmt): array
    {
        if ($idTp <= 0 || $idSmt <= 0) {
            return [];
        }

        $rows = DB::table('kelas_jadwal_mapel as jm')
            ->select('jm.id_kelas', 'jm.id_mapel', 'k.nama_kelas', 'm.nama_mapel')
            ->leftJoin('master_kelas as k', 'k.id_kelas', '=', 'jm.id_kelas')
            ->leftJoin('master_mapel as m', 'm.id_mapel', '=', 'jm.id_mapel')
            ->where('jm.id_tp', $idTp)
            ->where('jm.id_smt', $idSmt)
            ->where('jm.id_mapel', '>', 0)
            ->groupBy('jm.id_kelas', 'jm.id_mapel', 'k.nama_kelas', 'm.nama_mapel')
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('m.nama_mapel', 'ASC')
            ->get()->all();

        if (empty($rows)) {
            return [];
        }

        $assigned = [];
        $jabatan = DB::table('jabatan_guru')
            ->select('mapel_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->get()->all();

        foreach ($jabatan as $row) {
            foreach ($this->decodeMapelKelasAssignments($row->mapel_kelas) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $idMapel = (int) ($item['id_mapel'] ?? 0);
                $kelasList = $item['kelas_mapel'] ?? [];
                if ($idMapel <= 0 || ! is_array($kelasList)) {
                    continue;
                }

                foreach ($kelasList as $km) {
                    $idKelas = (int) (is_array($km) ? ($km['kelas'] ?? 0) : 0);
                    if ($idKelas > 0) {
                        $assigned[$idKelas.':'.$idMapel] = true;
                    }
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            if (isset($assigned[(int) $row->id_kelas.':'.(int) $row->id_mapel])) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Jadwal KBM guru hari ini (untuk quick-pick form jurnal).
     * Return list: id_jadwal, id_kelas, id_mapel, jam_ke, dari, sampai, nama_kelas, nama_mapel.
     */
    // TODO: tabel kelas_jadwal_mapel, master_kelas, master_mapel, jabatan_guru belum ada migrasinya
    public function getJadwalKbmGuruHariIni(int $idGuru, int $hari, int $idTp, int $idSmt): array
    {
        $mine = $this->getGuruAssignedPairs($idGuru, $idTp, $idSmt);
        if (empty($mine)) {
            return [];
        }

        $rows = DB::table('kelas_jadwal_mapel as jm')
            ->select('jm.id_jadwal', 'jm.id_kelas', 'jm.id_mapel', 'jm.jam_ke', 'jm.dari', 'jm.sampai', 'k.nama_kelas', 'm.nama_mapel')
            ->leftJoin('master_kelas as k', 'k.id_kelas', '=', 'jm.id_kelas')
            ->leftJoin('master_mapel as m', 'm.id_mapel', '=', 'jm.id_mapel')
            ->where('jm.id_tp', $idTp)
            ->where('jm.id_smt', $idSmt)
            ->where('jm.id_hari', $hari)
            ->where('jm.id_mapel', '>', 0)
            ->orderBy('jm.dari', 'ASC')
            ->get()->all();

        $out = [];
        foreach ($rows as $r) {
            if (isset($mine[(int) $r->id_kelas.':'.(int) $r->id_mapel])) {
                $out[] = $r;
            }
        }

        return $out;
    }

    /**
     * Get Jadwal Harian (Daily Schedule)
     */
    // TODO: tabel kelas_jadwal_mapel, master_mapel belum ada migrasinya
    public function getJadwalHarian($tp, $smt, $hari, $kelas = null)
    {
        $builder = DB::table('kelas_jadwal_mapel as jm')
            ->select('jm.*', 'm.kode', 'm.nama_mapel')
            ->leftJoin('master_mapel as m', 'm.id_mapel', '=', 'jm.id_mapel')
            ->where(['jm.id_tp' => $tp, 'jm.id_smt' => $smt, 'jm.id_hari' => $hari])
            ->orderBy('jm.dari', 'ASC');

        if ($kelas) {
            $builder->where('jm.id_kelas', $kelas);
        }

        $rows = $builder->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->id_kelas][] = $r;
        }

        return $result;
    }

    /**
     * Get all Jadwal Harian
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function getAllJadwalHarian($tp, $smt)
    {
        return DB::table('kelas_jadwal_mapel')
            ->where(['id_tp' => $tp, 'id_smt' => $smt])
            ->orderBy('id_kelas')
            ->orderBy('id_hari')
            ->orderBy('dari')
            ->get()->all();
    }

    /**
     * Insert batch Jadwal KBM
     */
    // TODO: tabel kelas_jadwal_kbm belum ada migrasinya
    public function insertBatchKbm($data)
    {
        if (empty($data)) {
            return true;
        }

        return DB::table('kelas_jadwal_kbm')->insert($data);
    }

    /**
     * Update batch Jadwal KBM.
     * Laravel tidak punya padanan updateBatch(): tiap baris di-update
     * satu per satu berdasarkan kunci id_kbm (semantik sama seperti CI4,
     * yang hanya meng-update baris yang sudah ada, bukan insert).
     */
    // TODO: tabel kelas_jadwal_kbm belum ada migrasinya
    public function updateBatchKbm($data)
    {
        if (empty($data)) {
            return true;
        }

        foreach ($data as $row) {
            if (! isset($row['id_kbm'])) {
                continue;
            }
            $update = $row;
            unset($update['id_kbm']);
            DB::table('kelas_jadwal_kbm')->where('id_kbm', $row['id_kbm'])->update($update);
        }

        return true;
    }

    /**
     * Insert batch Jadwal Mapel
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function insertBatchJadwal($data)
    {
        if (empty($data)) {
            return true;
        }

        return DB::table('kelas_jadwal_mapel')->insert($data);
    }

    /**
     * Update batch Jadwal Mapel.
     * Laravel tidak punya padanan updateBatch(): tiap baris di-update
     * satu per satu berdasarkan kunci id_jadwal (semantik sama seperti CI4).
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function updateBatchJadwal($data)
    {
        if (empty($data)) {
            return true;
        }

        foreach ($data as $row) {
            if (! isset($row['id_jadwal'])) {
                continue;
            }
            $update = $row;
            unset($update['id_jadwal']);
            DB::table('kelas_jadwal_mapel')->where('id_jadwal', $row['id_jadwal'])->update($update);
        }

        return true;
    }

    /**
     * Delete Jadwal by IDs
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function deleteJadwalByIds($ids)
    {
        if (empty($ids)) {
            return false;
        }

        return (bool) DB::table('kelas_jadwal_mapel')->whereIn('id_jadwal', $ids)->delete();
    }

    /**
     * Delete Jadwal Mapel by ID
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function deleteJadwalMapel($id)
    {
        return (bool) DB::table('kelas_jadwal_mapel')->where('id_jadwal', $id)->delete();
    }

    /**
     * Delete Jadwal by hari & tp/smt
     */
    // TODO: tabel kelas_jadwal_mapel belum ada migrasinya
    public function deleteJadwalHarian($tp, $smt, $hari = null, $includeMapel = false)
    {
        $builder = DB::table('kelas_jadwal_mapel')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt);

        if ($hari !== null) {
            $builder->where('id_hari', $hari);
        }

        if (! $includeMapel) {
            $builder->where('id_mapel', '0');
        }

        return (bool) $builder->delete();
    }

    /** Helper for Copy Jadwal */
    // TODO: tabel master_tp belum ada migrasinya
    public function getTpByTahun($tahun)
    {
        return DB::table('master_tp')->where('tahun', $tahun)->first();
    }

    // TODO: tabel kelas_jadwal_kbm, master_kelas belum ada migrasinya
    public function getAllJadwalKbmFull($tp, $smt)
    {
        return DB::table('kelas_jadwal_kbm as kbm')
            ->select('kbm.*', 'k.kode_kelas', 'k.nama_kelas')
            ->join('master_kelas as k', 'k.id_kelas', '=', 'kbm.id_kelas')
            ->where(['kbm.id_tp' => $tp, 'kbm.id_smt' => $smt])
            ->get()->all();
    }

    // TODO: tabel kelas_jadwal_mapel, master_kelas belum ada migrasinya
    public function getAllJadwalHarianFull($tp, $smt)
    {
        return DB::table('kelas_jadwal_mapel as jm')
            ->select('jm.*', 'k.kode_kelas')
            ->join('master_kelas as k', 'k.id_kelas', '=', 'jm.id_kelas')
            ->where(['jm.id_tp' => $tp, 'jm.id_smt' => $smt])
            ->get()->all();
    }
}

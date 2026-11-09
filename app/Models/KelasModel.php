<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/KelasModel.php
 * Tabel: master_kelas
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (master_kelas —
 * tabel `kelas` terbukti tidak pernah ada di database produksi).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class KelasModel extends Model
{
    protected $table = 'master_kelas';

    protected $fillable = [
    ];

    public $timestamps = false;
    public function getCatatanMapelKelas($kelas, $mapel, $id_tp, $id_smt)
    {
        return DB::table('kelas_catatan_mapel')
            ->where('id_kelas', $kelas)
            ->where('id_mapel', $mapel)
            ->where('type', '1')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->all();
    }

    // TODO: tabel kelas_siswa, master_siswa, kelas_catatan_mapel belum ada migrasinya
    public function getCatatanMapelSiswa($id_tp, $id_smt, $id_kelas, $id_mapel)
    {
        return DB::table('kelas_siswa as a')
            ->selectRaw('b.id_siswa, b.nama, b.nis, b.nisn, b.foto, (SELECT COUNT(id_siswa) FROM kelas_catatan_mapel c WHERE c.id_siswa = b.id_siswa AND c.id_mapel = ? AND c.type = \'2\') AS jml_catatan', [$id_mapel])
            ->leftJoin('master_siswa as b', 'b.id_siswa', '=', 'a.id_siswa')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->all();
    }

    // TODO: tabel kelas_catatan_mapel belum ada migrasinya
    public function getAllCatatanMapelSiswa($id_siswa, $id_mapel, $id_tp, $id_smt)
    {
        return DB::table('kelas_catatan_mapel')
            ->where('id_siswa', $id_siswa)
            ->where('id_mapel', $id_mapel)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->all();
    }

    // TODO: tabel master_guru, jabatan_guru, master_kelas belum ada migrasinya
    public function getGuruMapelKelas($id_guru, $tp, $smt)
    {
        return DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru', 'a.kode_guru', 'b.mapel_kelas', 'b.ekstra_kelas', 'd.nama_kelas')
            ->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
                $join->on('a.id_guru', '=', 'b.id_guru')
                    ->where('b.id_tp', '=', (int) $tp)
                    ->where('b.id_smt', '=', (int) $smt);
            })
            ->leftJoin('master_kelas as d', function ($join) use ($tp, $smt) {
                $join->on('b.id_kelas', '=', 'd.id_kelas')
                    ->where('d.id_tp', '=', (int) $tp)
                    ->where('d.id_smt', '=', (int) $smt);
            })
            ->where('a.id_guru', $id_guru)
            ->first();
    }

    // TODO: tabel kelas_jadwal_kbm belum ada migrasinya
    public function getJadwalKbm($tp, $smt, $kelas)
    {
        return DB::table('kelas_jadwal_kbm')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_kelas', $kelas)
            ->first();
    }

    // TODO: tabel kelas_jadwal_mapel, master_mapel belum ada migrasinya
    public function getJadwalMapelByMapel($kelas, $mapel, $tp, $smt)
    {
        $builder = DB::table('kelas_jadwal_mapel as a')
            ->select('a.*', 'b.nama_mapel', 'b.kode')
            ->leftJoin('master_mapel as b', 'a.id_mapel', '=', 'b.id_mapel')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt);

        if ($mapel != null) {
            $builder->where('a.id_mapel', $mapel);
        }

        if (is_array($kelas)) {
            $builder->whereIn('a.id_kelas', $kelas);
        } else {
            $builder->where('a.id_kelas', $kelas);
        }

        return $builder->get()->all();
    }

    // TODO: tabel kelas_catatan_wali belum ada migrasinya
    public function getCatatanKelas($kelas, $id_tp, $id_smt)
    {
        return DB::table('kelas_catatan_wali')
            ->where('id_kelas', $kelas)
            ->where('type', '1')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->all();
    }

    // TODO: tabel kelas_siswa, master_siswa, kelas_catatan_wali belum ada migrasinya
    public function getCatatanSiswa($id_tp, $id_smt, $id_kelas)
    {
        return DB::table('kelas_siswa as a')
            ->selectRaw('b.id_siswa, b.nama, b.nis, b.nisn, b.foto, (SELECT COUNT(id_siswa) FROM kelas_catatan_wali c WHERE c.id_siswa = b.id_siswa AND c.type = \'2\') AS jml_catatan')
            ->join('master_siswa as b', 'b.id_siswa', '=', 'a.id_siswa')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->all();
    }

    // TODO: tabel kelas_catatan_wali belum ada migrasinya
    public function getAllCatatanSiswa($id_siswa, $id_tp, $id_smt)
    {
        return DB::table('kelas_catatan_wali')
            ->where('id_siswa', $id_siswa)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->all();
    }

    // TODO: tabel kelas_struktur belum ada migrasinya
    public function getStrukturKelas($kelas)
    {
        return DB::table('kelas_struktur')
            ->where('id_kelas', $kelas)
            ->first();
    }

    public function dummyStruktur()
    {
        return [
            'id_kelas' => '',
            'ketua' => '',
            'wakil_ketua' => '',
            'sekretaris_1' => '',
            'sekretaris_2' => '',
            'bendahara_1' => '',
            'bendahara_2' => '',
            'sie_ekstrakurikuler' => '',
            'sie_upacara' => '',
            'sie_olahraga' => '',
            'sie_keagamaan' => '',
            'sie_keamanan' => '',
            'sie_ketertiban' => '',
            'sie_kebersihan' => '',
            'sie_keindahan' => '',
            'sie_kesehatan' => '',
            'sie_kekeluargaan' => '',
            'sie_humas' => '',
        ];
    }

    // TODO: tabel kelas_jadwal_mapel, master_mapel belum ada migrasinya
    public function loadJadwalSiswaHariIni($id_tp, $id_smt, $id_kelas, $id_hari)
    {
        return DB::table('kelas_jadwal_mapel as a')
            ->select('a.*', 'b.kode', 'b.nama_mapel')
            ->leftJoin('master_mapel as b', 'b.id_mapel', '=', 'a.id_mapel')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->where('a.id_hari', $id_hari)
            ->get()->all();
    }

    // TODO: tabel kelas_jadwal_mapel, master_mapel belum ada migrasinya
    public function loadJadwalSiswaSeminggu($id_tp, $id_smt, $id_kelas)
    {
        $result = DB::table('kelas_jadwal_mapel as a')
            ->select('a.*', 'b.kode', 'b.nama_mapel')
            ->leftJoin('master_mapel as b', 'b.id_mapel', '=', 'a.id_mapel')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->where('a.id_kelas', $id_kelas)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_hari][$row->jam_ke] = $row;
        }

        return $ret;
    }

    // TODO: tabel log_materi belum ada migrasinya
    public function getStatusMateriSiswaByJadwal($id_siswa, $arr_id_kjm)
    {
        $result = DB::table('log_materi')
            ->whereIn('id_materi', $arr_id_kjm)
            ->where('id_siswa', $id_siswa)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_materi] = $row;
        }

        return $ret;
    }

    // TODO: tabel kelas_catatan_mapel, master_guru belum ada migrasinya
    public function getCatatanMapelBySiswa($id_kelas, $id_tp, $id_smt)
    {
        // NOTE: seperti CI4, parameter $id_kelas tidak dipakai sebagai filter di query ini.
        return DB::table('kelas_catatan_mapel as a')
            ->select('a.*', 'b.nama_guru', 'b.nip', 'b.foto')
            ->join('master_guru as b', 'b.id_guru', '=', 'a.id_guru')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->all();
    }

    // TODO: tabel kelas_catatan_wali, jabatan_guru, master_guru belum ada migrasinya
    public function getCatatanSiswaBySiswa($id_kelas, $id_tp, $id_smt)
    {
        // NOTE: seperti CI4, parameter $id_kelas tidak dipakai sebagai filter di query ini.
        return DB::table('kelas_catatan_wali as a')
            ->select('a.*', 'c.nama_guru', 'c.nip', 'c.foto')
            ->join('jabatan_guru as b', 'b.id_kelas', '=', 'a.id_kelas')
            ->join('master_guru as c', 'c.id_guru', '=', 'b.id_guru')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->orderBy('tgl', 'DESC')
            ->get()->all();
    }

    // TODO: tabel kelas_catatan_mapel, master_guru, jabatan_guru, level_guru, master_mapel belum ada migrasinya
    public function getCatatanMapelSiswaDetail($id_catatan)
    {
        return DB::table('kelas_catatan_mapel as a')
            ->select('a.*', 'b.nama_guru', 'b.nip', 'b.foto', 'd.level as jabatan', 'e.nama_mapel', 'e.kode')
            ->join('master_guru as b', 'b.id_guru', '=', 'a.id_guru')
            ->join('jabatan_guru as c', 'c.id_guru', '=', 'a.id_guru')
            ->join('level_guru as d', 'd.id_level', '=', 'c.id_jabatan')
            ->join('master_mapel as e', 'e.id_mapel', '=', 'a.id_mapel')
            ->where('a.id_catatan', $id_catatan)
            ->first();
    }

    // TODO: tabel kelas_catatan_wali, jabatan_guru, master_guru, level_guru, master_kelas belum ada migrasinya
    public function getCatatanKelasSiswaDetail($id_catatan)
    {
        return DB::table('kelas_catatan_wali as a')
            ->select('a.*', 'c.nama_guru', 'c.nip', 'c.foto', 'e.level as jabatan', 'f.nama_kelas')
            ->join('jabatan_guru as b', 'b.id_kelas', '=', 'a.id_kelas')
            ->join('master_guru as c', 'c.id_guru', '=', 'b.id_guru')
            ->join('level_guru as e', 'e.id_level', '=', 'b.id_jabatan')
            ->join('master_kelas as f', 'f.id_kelas', '=', 'a.id_kelas')
            ->where('a.id_catatan', $id_catatan)
            ->first();
    }

    // TODO: tabel $table dinamis — nama tabel diteruskan pemanggil, belum tentu ada migrasinya
    public function getReading($table, $id_catatan)
    {
        return DB::table($table)
            ->select('reading', 'type', 'readed')
            ->where('id_catatan', $id_catatan)
            ->first();
    }

    // TODO: tabel master_kelas, master_jurusan, jabatan_guru, master_guru belum ada migrasinya
    public function getKelasList($tp, $smt)
    {
        // Get kelas list with wali kelas from jabatan_guru (id_jabatan = 4)
        // Use MIN() for joined columns to be compatible with MySQL strict mode
        return DB::table('master_kelas as a')
            ->select('a.id_kelas', 'a.nama_kelas', 'a.kode_kelas', 'a.level_id', 'a.jurusan_id', 'a.id_tp', 'a.id_smt', 'a.jumlah_siswa', 'a.guru_id')
            ->selectRaw('MIN(c.nama_jurusan) as nama_jurusan')
            ->selectRaw('MIN(g.nama_guru) as nama_guru')
            ->leftJoin('master_jurusan as c', 'a.jurusan_id', '=', 'c.id_jurusan')
            // Join wali kelas from jabatan_guru where id_jabatan = 4
            ->leftJoin('jabatan_guru as jg', function ($join) {
                $join->on('jg.id_kelas', '=', 'a.id_kelas')
                    ->where('jg.id_jabatan', '=', '4')
                    ->whereColumn('jg.id_tp', 'a.id_tp')
                    ->whereColumn('jg.id_smt', 'a.id_smt');
            })
            ->leftJoin('master_guru as g', 'g.id_guru', '=', 'jg.id_guru')
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->groupBy('a.id_kelas', 'a.nama_kelas', 'a.kode_kelas', 'a.level_id', 'a.jurusan_id', 'a.id_tp', 'a.id_smt', 'a.jumlah_siswa', 'a.guru_id')
            ->orderBy('a.level_id', 'ASC')
            ->get()->all();
    }

    /**
     * Get all kelas from all TP/SMT (for building nested structure)
     */
    // TODO: tabel master_kelas, master_guru, master_jurusan belum ada migrasinya
    public function getAllKelas()
    {
        return DB::table('master_kelas as a')
            ->select('a.*', 'b.nama_guru', 'c.nama_jurusan')
            ->leftJoin('master_guru as b', 'a.guru_id', '=', 'b.id_guru')
            ->leftJoin('master_jurusan as c', 'a.jurusan_id', '=', 'c.id_jurusan')
            ->orderBy('a.id_tp', 'DESC')
            ->orderBy('a.id_smt', 'DESC')
            ->orderBy('a.level_id', 'ASC')
            ->get()->all();
    }

    // TODO: tabel master_jurusan belum ada migrasinya
    public function get_jurusan()
    {
        return DB::table('master_jurusan')->get()->all();
    }

    // TODO: tabel master_jurusan belum ada migrasinya
    public function getJurusanById($id)
    {
        return DB::table('master_jurusan')->where('id_jurusan', $id)->first();
    }

    // TODO: tabel level_guru belum ada migrasinya
    public function getLevel($jenjang)
    {
        return DB::table('level_guru')->where('jenjang', $jenjang)->get()->all();
    }

    // TODO: tabel master_guru belum ada migrasinya
    public function get_guru()
    {
        return DB::table('master_guru')->get()->all();
    }

    // TODO: tabel master_kelas, master_jurusan, level_kelas, jabatan_guru, master_guru belum ada migrasinya
    public function get_one($id, $id_tp = null, $id_smt = null)
    {
        // Get kelas data first - using level_kelas table for class level (not level_guru)
        $builder = DB::table('master_kelas as a')
            ->select('a.*', 'c.nama_jurusan', 'd.level')
            ->leftJoin('master_jurusan as c', 'a.jurusan_id', '=', 'c.id_jurusan')
            ->leftJoin('level_kelas as d', 'a.level_id', '=', 'd.id_level')
            ->where('a.id_kelas', $id);

        if ($id_tp != null) {
            $builder->where('a.id_tp', $id_tp);
        }
        if ($id_smt != null) {
            $builder->where('a.id_smt', $id_smt);
        }

        $kelas = $builder->first();

        // Get wali kelas from jabatan_guru table (id_jabatan = 4 means Wali Kelas)
        if ($kelas) {
            $waliKelas = DB::table('jabatan_guru as jg')
                ->select('g.id_guru', 'g.nama_guru')
                ->join('master_guru as g', 'g.id_guru', '=', 'jg.id_guru')
                ->where('jg.id_kelas', $id)
                ->where('jg.id_jabatan', '4') // 4 = Wali Kelas
                ->where('jg.id_tp', $kelas->id_tp)
                ->where('jg.id_smt', $kelas->id_smt)
                ->first();

            $kelas->nama_guru = $waliKelas->nama_guru ?? null;
            $kelas->guru_id = $waliKelas->id_guru ?? null;
        }

        return $kelas;
    }

    // TODO: tabel kelas_siswa, master_siswa belum ada migrasinya
    public function get_siswa_kelas($id, $tp, $smt)
    {
        return DB::table('kelas_siswa as a')
            ->select('a.*', 'b.id_siswa', 'b.nama', 'b.nis', 'b.nisn', 'b.foto', 'b.jenis_kelamin')
            ->join('master_siswa as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('a.id_kelas', $id)
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->orderBy('b.nama', 'ASC')
            ->get()->all();
    }

    // TODO: tabel kelas_siswa, master_siswa belum ada migrasinya (via get_siswa_kelas)
    public function getKelasSiswa($id, $tp, $smt)
    {
        return $this->get_siswa_kelas($id, $tp, $smt);
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getNamaKelasById($arr_id)
    {
        if (empty($arr_id)) {
            return [];
        }
        $result = DB::table('master_kelas')
            ->whereIn('id_kelas', $arr_id)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->nama_kelas;
        }

        return $ret;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getNamaKelasByKode($arr_kode)
    {
        if (empty($arr_kode)) {
            return [];
        }
        $result = DB::table('master_kelas')
            ->whereIn('kode_kelas', $arr_kode)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kelas] = $row->nama_kelas;
        }

        return $ret;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getNamaKelasByNama($id_tp, $id_smt)
    {
        $result = DB::table('master_kelas')
            ->select('id_kelas', 'nama_kelas')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_kelas] = $row->nama_kelas;
        }

        return $ret;
    }

    // TODO: tabel kelas_siswa, master_kelas belum ada migrasinya
    public function get_siswa_kelas_baru($id_tp, $id_smt)
    {
        return DB::table('kelas_siswa as a')
            ->select('a.id_siswa', 'b.nama_kelas')
            ->join('master_kelas as b', 'a.id_kelas', '=', 'b.id_kelas')
            ->where('a.id_tp', $id_tp)
            ->where('a.id_smt', $id_smt)
            ->get()->all();
    }

    // TODO: tabel kelas_ekstra belum ada migrasinya
    public function getKelasEkskul($kelas, $tp, $smt)
    {
        return DB::table('kelas_ekstra')
            ->where('id_kelas', $kelas)
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->get()->all();
    }

    /**
     * Get available wali kelas (teachers with jabatan wali kelas)
     */
    // TODO: tabel jabatan_guru, master_guru belum ada migrasinya
    public function getWaliKelas($tp, $smt)
    {
        $result = DB::table('jabatan_guru as a')
            ->select('a.id_guru', 'b.nama_guru')
            ->leftJoin('master_guru as b', 'b.id_guru', '=', 'a.id_guru')
            ->where('a.id_jabatan', '4') // 4 = Wali Kelas
            ->where('a.id_tp', $tp)
            ->where('a.id_smt', $smt)
            ->get()->all();

        $ret = ['' => 'Pilih Wali Kelas :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_guru] = $row->nama_guru;
            }
        }

        return $ret;
    }

    /**
     * Get status siswa kelas (for removing from class)
     */
    // TODO: tabel kelas_siswa belum ada migrasinya
    public function get_status_siswa_kelas($id_kelas, $id_tp, $id_smt)
    {
        $result = DB::table('kelas_siswa')
            ->where('id_kelas', $id_kelas)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->all();

        $ret = [];
        foreach ($result as $row) {
            $ret[$row->id_siswa] = $row;
        }

        return $ret;
    }
}

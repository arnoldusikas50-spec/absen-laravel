<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/DropdownModel.php
 * Tabel: master_guru
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (master_guru).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class DropdownModel extends Model
{
    protected $table = 'master_guru';

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

    public function getAllSesi()
    {
        return [];
    }

    public function getAllRuang()
    {
        return [];
    }

    // TODO: tabel master_kelompok_mapel belum ada migrasinya
    public function getDataKelompokMapel()
    {
        $result = DB::table('master_kelompok_mapel')->orderBy('kode_kel_mapel')->get()->all();
        $ret = [];
        foreach ($result as $row) {
            $ret[$row->kode_kel_mapel] = $row->nama_kel_mapel;
        }

        return $ret;
    }

    // TODO: tabel master_mapel belum ada migrasinya
    public function getAllMapel()
    {
        $result = DB::table('master_mapel')
            ->select('id_mapel', 'nama_mapel')
            ->where('status', '1')
            ->orderBy('urutan_tampil')
            ->get()->all();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_mapel] = $row->nama_mapel;
            }
        }

        return $ret;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getAllKelas($tp, $smt, $level = null)
    {
        $builder = DB::table('master_kelas')
            ->select('id_kelas', 'nama_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt);

        if ($level != null) {
            $builder->where('level_id', $level);
        }

        $result = $builder->orderBy('level_id', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()->all();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_kelas] = $row->nama_kelas;
            }
        }

        return $ret;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getAllKelasByArrayId($tp, $smt, $arrId)
    {
        if (empty($arrId)) {
            return [];
        }

        $result = DB::table('master_kelas')
            ->select('id_kelas', 'nama_kelas')
            ->where('id_tp', $tp)
            ->whereIn('id_kelas', $arrId)
            ->get()->all();

        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_kelas] = $row->nama_kelas;
            }
        }

        return $ret;
    }

    // TODO: tabel master_guru, users belum ada migrasinya
    public function getAllGuru()
    {
        $result = DB::table('master_guru as a')
            ->select('a.id_guru', 'a.nama_guru')
            ->join('users as e', 'a.username', '=', 'e.username')
            ->get()->all();

        $ret = ['0' => 'Pilih Guru :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_guru] = $row->nama_guru;
            }
        }

        return $ret;
    }

    // TODO: tabel level_guru belum ada migrasinya
    public function getAllLevelGuru()
    {
        $result = DB::table('level_guru')->get()->all();
        $ret = ['' => 'Pilih Jabatan :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_level] = $row->level;
            }
        }

        return $ret;
    }

    // TODO: tabel master_ekstra belum ada migrasinya
    public function getAllEkskul()
    {
        $result = DB::table('master_ekstra')->orderBy('nama_ekstra')->get()->all();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_ekstra] = $row->nama_ekstra;
            }
        }

        return $ret;
    }

    // TODO: tabel master_ekstra belum ada migrasinya
    public function getAllKodeEkskul()
    {
        $result = DB::table('master_ekstra')->orderBy('nama_ekstra')->get()->all();
        $ret = [];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_ekstra] = $row->kode_ekstra;
            }
        }

        return $ret;
    }

    // TODO: tabel master_jurusan belum ada migrasinya
    public function getDataJurusan()
    {
        $result = DB::table('master_jurusan')->get()->all();
        $ret = ['' => 'Pilih Jurusan :'];
        if ($result) {
            foreach ($result as $row) {
                $ret[$row->id_jurusan] = $row->nama_jurusan;
            }
        }

        return $ret;
    }

    public function getAllLevelKelas($jenjang)
    {
        if ($jenjang == '1') {
            $levels = ['' => 'Pilih Level :', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
        } elseif ($jenjang == '2') {
            $levels = ['' => 'Pilih Level :', '7' => '7', '8' => '8', '9' => '9'];
        } else {
            $levels = ['' => 'Pilih Level :', '10' => '10', '11' => '11', '12' => '12'];
        }

        return $levels;
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getNamaKelasById($tp, $smt, $id_kelas)
    {
        $result = DB::table('master_kelas')
            ->select('nama_kelas')
            ->where('id_tp', $tp)
            ->where('id_smt', $smt)
            ->where('id_kelas', $id_kelas)
            ->first();

        return $result ? $result->nama_kelas : '';
    }

    // TODO: tabel master_panitia belum ada migrasinya
    public function getAllPanitiaPengawas()
    {
        return DB::table('master_panitia')
            ->select('id_panitia', 'nama_panitia', 'ruang')
            ->get()
            ->all();
    }
}

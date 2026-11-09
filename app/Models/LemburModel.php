<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/LemburModel.php
 * Tabel: presensi_lembur
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class LemburModel extends Model
{
    protected $table = 'presensi_lembur';

    protected $fillable = [
        'user_id',
        'id_guru',
        'tgl',
        'jam_mulai',
        'jam_selesai',
        'total_jam',
        'keterangan',
        'status',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'id_guru' => 'integer',
        'tgl' => 'date',
        'total_jam' => 'decimal:2',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getLemburUser($userId, $limit = 20)
    {
        return DB::table('presensi_lembur')
            ->where('user_id', $userId)
            ->orderBy('tgl', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()->all();
    }

    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getPending()
    {
        return DB::table('presensi_lembur as pl')
            ->selectRaw("pl.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru, mg.nip")
            ->leftJoin('master_guru as mg', 'mg.id_guru', '=', 'pl.id_guru')
            ->leftJoin('users as u', 'u.id', '=', 'pl.user_id')
            ->where('pl.status', 'PENDING')
            ->orderBy('pl.tgl', 'DESC')
            ->orderBy('pl.created_at', 'DESC')
            ->get()->all();
    }

    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getRekap($bulan, $tahun)
    {
        $bulan = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $tahun;
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return DB::table('presensi_lembur as pl')
            ->selectRaw("pl.user_id,
                COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru,
                mg.nip,
                COUNT(pl.id) as total_lembur,
                COALESCE(SUM(pl.total_jam), 0) as total_jam,
                SUM(CASE WHEN pl.status = 'APPROVED' THEN pl.total_jam ELSE 0 END) as jam_disetujui,
                SUM(CASE WHEN pl.status = 'APPROVED' THEN 1 ELSE 0 END) as lembur_disetujui,
                SUM(CASE WHEN pl.status = 'PENDING' THEN pl.total_jam ELSE 0 END) as jam_pending,
                SUM(CASE WHEN pl.status = 'PENDING' THEN 1 ELSE 0 END) as lembur_pending,
                SUM(CASE WHEN pl.status = 'REJECTED' THEN 1 ELSE 0 END) as lembur_ditolak")
            ->leftJoin('master_guru as mg', 'mg.id_guru', '=', 'pl.id_guru')
            ->leftJoin('users as u', 'u.id', '=', 'pl.user_id')
            ->where('pl.tgl', '>=', $tglAwal)
            ->where('pl.tgl', '<=', $tglAkhir)
            ->groupByRaw('pl.user_id, mg.nama_guru, mg.nip, u.first_name, u.last_name, u.username')
            ->orderBy('jam_disetujui', 'DESC')
            ->orderBy('total_jam', 'DESC')
            ->get()->all();
    }

    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getRiwayat($bulan, $tahun, $limit = 200)
    {
        $bulan = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $tahun;
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return DB::table('presensi_lembur as pl')
            ->selectRaw("pl.*, COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru, mg.nip")
            ->leftJoin('master_guru as mg', 'mg.id_guru', '=', 'pl.id_guru')
            ->leftJoin('users as u', 'u.id', '=', 'pl.user_id')
            ->where('pl.tgl', '>=', $tglAwal)
            ->where('pl.tgl', '<=', $tglAkhir)
            ->orderBy('pl.tgl', 'DESC')
            ->orderBy('pl.created_at', 'DESC')
            ->limit($limit)
            ->get()->all();
    }
}

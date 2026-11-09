<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/LeaveModel.php
 * Tabel: absensi_izin
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class LeaveModel extends Model
{
    protected $table = 'absensi_izin';

    protected $fillable = [
        'user_id',
        'id_guru',
        'tgl_mulai',
        'tgl_selesai',
        'jenis',
        'kategori_cuti',
        'keterangan',
        'status',
        'bukti',
        'verifikasi_catatan',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'id_guru' => 'integer',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Bentuk kanonik + bentuk angka lama. */
    public const STATUS_PENDING = ['pending', '0'];

    public const STATUS_APPROVED = ['approved', '1'];

    public const STATUS_REJECTED = ['rejected', '2'];

    /**
     * Referensi Konstanta status yang SEDANG mengonsumsi kuota
     * (pending + approved, termasuk bentuk angka legacy). Dipakai
     * PresensiAdmin untuk guard input manual, supaya angka yang
     * meng-kunci kuota sama dengan IzinKuotaService::STATUS_MAKAN_KUOTA.
     */
    public const STATUS_MAKAN_KUOTA = ['pending', 'approved', '0', '1'];

    /**
     * Terjemahkan filter status ke sekumpulan nilai yang perlu dicocokkan,
     * termasuk padanan angka legacy-nya.
     */
    public static function statusSet(string $status): array
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'pending' => self::STATUS_PENDING,
            'approved' => self::STATUS_APPROVED,
            'rejected' => self::STATUS_REJECTED,
            default => [$status],
        };
    }

    /**
     * Semua pengajuan pending (status = 'pending') beserta info guru.
     */
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getPending(): array
    {
        // NOTE: CI4 memakai konstanta telanjang STATUS_PENDING di sini
        // (tanpa self::) yang di PHP 8 melempar Error karena konstanta
        // global itu tidak ada; di-port memakai self::STATUS_PENDING
        // sesuai maksud aslinya.
        return DB::table('absensi_izin as a')
            ->select('a.*', 'mg.nama_guru', 'mg.nip', 'u.username', 'u.foto')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('master_guru as mg', 'mg.id_user', '=', 'u.id')
            ->whereIn('a.status', self::STATUS_PENDING)
            ->orderBy('a.created_at', 'DESC')
            ->get()->all();
    }

    /**
     * Semua pengajuan dengan join lengkap (termasuk verifikator).
     * Filter opsional: bulan, tahun, status, jenis.
     */
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getAll(
        string|int $bulan = '',
        string|int $tahun = '',
        string $status = '',
        string $jenis = ''
    ): array {
        $builder = DB::table('absensi_izin as i')
            ->selectRaw('i.*, u.first_name, u.last_name, u.username, u.foto,
                      g.nama_guru, g.nip,
                      adm.first_name AS verifier_first, adm.last_name AS verifier_last')
            ->leftJoin('users as u', 'u.id', '=', 'i.user_id')
            ->leftJoin('master_guru as g', 'g.id_user', '=', 'u.id')
            ->leftJoin('users as adm', 'adm.id', '=', 'i.verified_by');

        if ($bulan && is_numeric($bulan)) {
            $bulan = (int) $bulan;
            $builder->whereRaw("(MONTH(i.tgl_mulai) = {$bulan} OR MONTH(i.tgl_selesai) = {$bulan})");
        }
        if ($tahun && is_numeric($tahun)) {
            $tahun = (int) $tahun;
            $builder->whereRaw("(YEAR(i.tgl_mulai) = {$tahun} OR YEAR(i.tgl_selesai) = {$tahun})");
        }
        if ($status && $status !== 'all') {
            $filter = strtolower(trim($status));
            $builder->whereIn('i.status', self::statusSet($filter));
        }
        if ($jenis && $jenis !== 'all') {
            // Handle alias ijin/izin
            $j = strtolower($jenis);
            if ($j === 'izin') {
                $builder->where(function ($query) {
                    $query->where('i.jenis', 'izin')
                        ->orWhere('i.jenis', 'ijin');
                });
            } else {
                $builder->where('i.jenis', $j);
            }
        }

        return $builder->orderBy('i.created_at', 'DESC')->get()->all();
    }

    /**
     * Satu record dengan join guru + verifikator.
     */
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getWithGuru(int $id): ?object
    {
        return DB::table('absensi_izin as i')
            ->selectRaw('i.*, g.nama_guru, g.nip, u.foto,
                      adm.first_name AS verifier_first, adm.last_name AS verifier_last')
            ->leftJoin('users as u', 'u.id', '=', 'i.user_id')
            ->leftJoin('master_guru as g', 'g.id_user', '=', 'u.id')
            ->leftJoin('users as adm', 'adm.id', '=', 'i.verified_by')
            ->where('i.id', $id)
            ->first();
    }
}

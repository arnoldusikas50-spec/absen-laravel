<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/KalenderModel.php
 * Tabel: kalender_akademik
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class KalenderModel extends Model
{
    protected $table = 'kalender_akademik';

    protected $fillable = [
        'tgl_mulai',
        'tgl_selesai',
        'judul',
        'deskripsi',
        'tipe',
        'warna',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getEvents($bulan, $tahun)
    {
        $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        return $this->getEventsByRange($tglAwal, $tglAkhir);
    }

    /**
     * Event yang menyentuh rentang tanggal (termasuk event multi-hari yang
     * dimulai sebelum $tglAwal dan berakhir setelahnya).
     */
    public function getEventsByRange($tglAwal, $tglAkhir)
    {
        return DB::table('kalender_akademik')
            ->where('tgl_mulai', '<=', $tglAkhir)
            ->where(function ($q) use ($tglAwal) {
                $q->where(function ($q2) use ($tglAwal) {
                    $q2->whereNotNull('tgl_selesai')
                        ->where('tgl_selesai', '>=', $tglAwal);
                })->orWhere(function ($q2) use ($tglAwal) {
                    $q2->whereNull('tgl_selesai')
                        ->where('tgl_mulai', '>=', $tglAwal);
                });
            })
            ->orderBy('tgl_mulai', 'ASC')
            ->orderBy('tgl_selesai', 'ASC')
            ->get()->all();
    }

    /**
     * Hari libur absensi (tanggal merah/libur sekolah) dalam rentang tanggal.
     */
    public function getHolidaysByRange($tglAwal, $tglAkhir)
    {
        return DB::table('absensi_hari_libur')
            ->where('tanggal', '>=', $tglAwal)
            ->where('tanggal', '<=', $tglAkhir)
            ->orderBy('tanggal', 'ASC')
            ->get()->all();
    }

    public function getUpcoming($limit = 5)
    {
        return DB::table('kalender_akademik')
            ->where('tgl_mulai', '>=', date('Y-m-d'))
            ->orderBy('tgl_mulai', 'ASC')
            ->limit($limit)
            ->get()->all();
    }

    public function getHolidays($bulan, $tahun)
    {
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        // From kalender_akademik
        $events = DB::table('kalender_akademik')
            ->selectRaw("tgl_mulai as tgl, judul, 'KALENDER' as sumber")
            ->where('tipe', 'LIBUR')
            ->where('tgl_mulai', '>=', $tglAwal)
            ->where('tgl_mulai', '<=', $tglAkhir)
            ->get()->all();

        // From absensi_hari_libur
        $libur = DB::table('absensi_hari_libur')
            ->selectRaw("tanggal as tgl, keterangan as judul, 'ABSENSI' as sumber")
            ->where('tanggal', '>=', $tglAwal)
            ->where('tanggal', '<=', $tglAkhir)
            ->get()->all();

        return array_merge($events, $libur);
    }

    public function getEvent($id)
    {
        return static::find($id);
    }
}

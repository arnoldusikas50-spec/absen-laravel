<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/PresensiBdrModel.php
 * Tabel: presensi_bdr
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class PresensiBdrModel extends Model
{
    protected $table = 'presensi_bdr';

    protected $fillable = [
        'lingkup',
        'id_kelas',
        'level_id',
        'tgl_mulai',
        'tgl_selesai',
        'judul',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_kelas' => 'integer',
        'level_id' => 'integer',
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Baris jadwal yang aktif pada satu tanggal.
     *
     * @return array<int, object>
     */
    public function getRowsUntukTanggal(string $tgl): array
    {
        if (! Schema::hasTable($this->getTable())) {
            return [];
        }

        $q = DB::table($this->getTable())
            ->where('tgl_mulai', '<=', $tgl);
        $q->where(function ($query) use ($tgl) {
            $query->whereNull('tgl_selesai')
                ->orWhere('tgl_selesai', '>=', $tgl);
        });

        return $q->orderBy('id', 'ASC')->get()->all();
    }

    /**
     * Baris jadwal yang bersinggungan dengan sebuah rentang tanggal.
     *
     * @return array<int, object>
     */
    public function getRowsDalamRentang(string $tglAwal, string $tglAkhir): array
    {
        if (! Schema::hasTable($this->getTable())) {
            return [];
        }

        $q = DB::table($this->getTable())
            ->where('tgl_mulai', '<=', $tglAkhir);
        $q->where(function ($query) use ($tglAwal) {
            $query->whereNull('tgl_selesai')
                ->orWhere('tgl_selesai', '>=', $tglAwal);
        });

        return $q->orderBy('tgl_mulai', 'ASC')->orderBy('id', 'ASC')->get()->all();
    }

    /**
     * Daftar jadwal untuk halaman admin, lengkap dengan nama lingkup terbaca.
     *
     * @return array<int, object>
     */
    // TODO: tabel master_kelas belum ada migrasinya
    // TODO: tabel level_kelas belum ada migrasinya
    public function getDaftar(int $limit = 200, ?string $sejak = null): array
    {
        if (! Schema::hasTable($this->getTable())) {
            return [];
        }

        $q = DB::table('presensi_bdr as b')
            ->selectRaw('b.*, mk.nama_kelas, lk.level')
            ->leftJoin('master_kelas as mk', 'mk.id_kelas', '=', 'b.id_kelas')
            ->leftJoin('level_kelas as lk', 'lk.id_level', '=', 'b.level_id')
            ->orderBy('b.tgl_mulai', 'DESC')
            ->orderBy('b.id', 'DESC')
            ->limit($limit);
        if ($sejak !== null) {
            // Jadwal yang masih berlangsung pada/ sesudah tanggal ini
            // (tgl_selesai kosong = satu hari, dipakai apa adanya).
            $q->where(function ($query) use ($sejak) {
                $query->where('tgl_selesai', '>=', $sejak)
                    ->orWhere('tgl_mulai', '>=', $sejak);
            });
        }

        return $q->get()->all();
    }

    /** Bentrok tanggal yang sama untuk lingkup/kelas yang sama (cegah dobel). */
    public function adaBentrok(string $lingkup, ?int $idKelas, ?int $levelId, string $tglMulai, ?string $tglSelesai, int $kecualikanId = 0): bool
    {
        if (! Schema::hasTable($this->getTable())) {
            return false;
        }
        $tglSelesai = $tglSelesai ?: $tglMulai;

        $q = DB::table($this->getTable())->where('lingkup', $lingkup);
        if ($kecualikanId > 0) {
            $q->where('id', '!=', $kecualikanId);
        }
        if ($lingkup === 'KELAS') {
            $q->where('id_kelas', (int) $idKelas);
        } elseif ($lingkup === 'TINGKAT') {
            $q->where('level_id', (int) $levelId);
        }

        // Overlap rentang: mulai <= selesaiBaru AND (selesai >= mulaiBaru OR selesai NULL)
        $q->where('tgl_mulai', '<=', $tglSelesai);
        $q->where(function ($query) use ($tglMulai) {
            $query->where('tgl_selesai', '>=', $tglMulai)->orWhereNull('tgl_selesai');
        });

        return $q->count() > 0;
    }
}

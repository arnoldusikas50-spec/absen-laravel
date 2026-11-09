<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/SiswaPresensiModel.php
 * Tabel: presensi_siswa
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class SiswaPresensiModel extends Model
{
    protected $table = 'presensi_siswa';

    protected $fillable = [
        'id_kelas',
        'id_siswa',
        'id_mapel',
        'id_guru',
        'tgl',
        'jam',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_kelas' => 'integer',
        'id_siswa' => 'integer',
        'id_mapel' => 'integer',
        'id_guru' => 'integer',
        'tgl' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'lat_masuk' => 'decimal:7',
        'lng_masuk' => 'decimal:7',
        'akurasi_masuk' => 'integer',
        'lokasi_id' => 'integer',
        'lat_keluar' => 'decimal:7',
        'lng_keluar' => 'decimal:7',
        'pulang_tidak_tercatat' => 'boolean',
        'denda' => 'decimal:2',
    ];

    /**
     * Hanya siswa AKTIF (buku_induk.status = '1').
     * Siswa LULUS (2) / PINDAH (3) / KELUAR (4) dikecualikan dari
     * daftar absen, monitoring, rekap, dan poin. Baris tanpa buku_induk
     * (data lama) dianggap AKTIF agar tidak hilang diam-diam.
     */
    // TODO: tabel buku_induk belum ada migrasinya
    private function filterSiswaAktif($builder, string $aliasMs = 'ms'): void
    {
        try {
            if (! Schema::hasTable('buku_induk')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $builder->leftJoin(
            'buku_induk as bi_aktif',
            'bi_aktif.id_siswa',
            '=',
            "{$aliasMs}.id_siswa"
        )->where(function ($q) {
            $q->where('bi_aktif.status', '1')
                ->orWhereNull('bi_aktif.id_siswa');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Daftar siswa berdasarkan kelas, TP, SMT
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel kelas_siswa, master_siswa, buku_induk belum ada migrasinya
    public function getSiswaByKelas(int $idKelas, int $idTp, int $idSmt): array
    {
        $b = DB::table('kelas_siswa as ks')
            ->select('ks.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn', 'ms.foto', 'ms.jenis_kelamin')
            ->join('master_siswa as ms', 'ms.id_siswa', '=', 'ks.id_siswa')
            ->where('ks.id_kelas', $idKelas)
            ->where('ks.id_tp', $idTp)
            ->where('ks.id_smt', $idSmt);
        $this->filterSiswaAktif($b, 'ms');

        return $b->orderBy('ms.nama', 'ASC')
            ->get()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mata pelajaran yang diajarkan di kelas ini
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel kelas_jadwal_mapel, master_mapel belum ada migrasinya
    public function getMapelByKelas(int $idKelas, int $idTp, int $idSmt): array
    {
        // Ambil dari jadwal mapel kelas jika ada
        $result = DB::table('kelas_jadwal_mapel as kjm')
            ->select('mm.id_mapel', 'mm.nama_mapel')
            ->join('master_mapel as mm', 'mm.id_mapel', '=', 'kjm.id_mapel')
            ->where('kjm.id_kelas', $idKelas)
            ->where('kjm.id_tp', $idTp)
            ->where('kjm.id_smt', $idSmt)
            ->groupBy('mm.id_mapel')
            ->orderBy('mm.nama_mapel', 'ASC')
            ->get()->all();

        // Fallback: semua mapel aktif
        if (empty($result)) {
            $result = DB::table('master_mapel')
                ->select('id_mapel', 'nama_mapel')
                ->where('status', '1')
                ->orderBy('nama_mapel', 'ASC')
                ->get()->all();
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Presensi yang sudah ada untuk kelas + mapel + tanggal
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel master_siswa, buku_induk belum ada migrasinya
    public function getPresensiSiswa(
        int $idKelas,
        ?int $idMapel,
        string $tgl
    ): array {
        $builder = DB::table('presensi_siswa as ps')
            ->selectRaw('ps.id, ps.id_kelas, ps.id_siswa, ps.id_guru,
                      ps.tgl, ps.jam, ps.jam_masuk, ps.jam_keluar,
                      ps.status, ps.keterangan,
                      ms.nama, ms.nis, ms.foto')
            ->join('master_siswa as ms', 'ms.id_siswa', '=', 'ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl', $tgl);
        $this->filterSiswaAktif($builder, 'ms');

        // Jika idMapel dikirim (mode lama), filter; mode baru (null) ambil semua per hari
        if ($idMapel !== null) {
            $builder->where('ps.id_mapel', $idMapel);
        }

        return $builder->orderBy('ms.nama', 'ASC')->get()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rekap bulanan per kelas
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel master_siswa, buku_induk belum ada migrasinya
    public function getRekapBulanan(int $idKelas, string $bulan, string $tahun): array
    {
        $bulan = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $tglAwal = "{$tahun}-{$bulan}-01";
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $b = DB::table('presensi_siswa as ps')
            ->selectRaw("
                ps.id_siswa, ms.nama, ms.nis, ms.nisn,
                SUM(CASE WHEN ps.status = 'HADIR'            THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN ps.status = 'SAKIT'            THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN ps.status = 'IZIN'             THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN ps.status = 'ALPA'             THEN 1 ELSE 0 END) AS alpa,
                SUM(CASE WHEN ps.status = 'BDR'              THEN 1 ELSE 0 END) AS bdr,
                SUM(CASE WHEN ps.status = 'TANPA_KETERANGAN' THEN 1 ELSE 0 END) AS tanpa_keterangan,
                COUNT(ps.id) AS total
            ")
            ->join('master_siswa as ms', 'ms.id_siswa', '=', 'ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl', '>=', $tglAwal)
            ->where('ps.tgl', '<=', $tglAkhir);
        $this->filterSiswaAktif($b, 'ms');

        return $b->groupBy('ps.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn')
            ->orderBy('ms.nama', 'ASC')
            ->get()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Monitoring harian: siswa + status presensi hari tertentu
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel kelas_siswa, master_siswa, master_guru, buku_induk belum ada migrasinya
    public function getMonitoringHarian(int $idKelas, string $tgl, ?int $idTp, ?int $idSmt): array
    {
        $select = '
                ms.id_siswa, ms.nama, ms.nis, ms.foto,
                ps.id      AS presensi_id,
                ps.status,
                ps.jam,
                ps.jam_masuk,
                ps.jam_keluar,
                ps.keterangan,
                ps.id_guru,
                mg.nama_guru
            ';

        // Penanda dipasang cron saat buku pulang ditutup. Kolomnya bisa belum
        // ada di DB yang migration-nya belum jalan — jangan sampai monitoring
        // mati hanya karena kolom tambahan.
        if (Schema::hasColumn('presensi_siswa', 'pulang_tidak_tercatat')) {
            $select .= ',ps.pulang_tidak_tercatat';
        }

        $builder = DB::table('kelas_siswa as ks')
            ->selectRaw($select)
            ->join('master_siswa as ms', 'ms.id_siswa', '=', 'ks.id_siswa')
            ->leftJoin('presensi_siswa as ps', function ($join) use ($tgl) {
                $join->on('ps.id_siswa', '=', 'ks.id_siswa')
                    ->on('ps.id_kelas', '=', 'ks.id_kelas')
                    ->where('ps.tgl', '=', $tgl);
            })
            ->leftJoin('master_guru as mg', 'mg.id_guru', '=', 'ps.id_guru')
            ->where('ks.id_kelas', $idKelas);

        if ($idTp) {
            $builder->where('ks.id_tp', $idTp);
        }
        if ($idSmt) {
            $builder->where('ks.id_smt', $idSmt);
        }
        $this->filterSiswaAktif($builder, 'ms');

        return $builder->orderBy('ms.nama', 'ASC')->get()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Statistik ringkas harian per kelas
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel master_siswa, buku_induk belum ada migrasinya
    public function getStatHarian(int $idKelas, string $tgl): object
    {
        $b = DB::table('presensi_siswa as ps')
            ->selectRaw("
                SUM(CASE WHEN ps.status = 'HADIR'            THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN ps.status = 'SAKIT'            THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN ps.status = 'IZIN'             THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN ps.status = 'ALPA'             THEN 1 ELSE 0 END) AS alpa,
                SUM(CASE WHEN ps.status = 'BDR'              THEN 1 ELSE 0 END) AS bdr,
                SUM(CASE WHEN ps.status = 'TANPA_KETERANGAN' THEN 1 ELSE 0 END) AS tanpa,
                COUNT(ps.id) AS total
            ")
            ->join('master_siswa as ms', 'ms.id_siswa', '=', 'ps.id_siswa')
            ->where('ps.id_kelas', $idKelas)
            ->where('ps.tgl', $tgl);
        $this->filterSiswaAktif($b, 'ms');
        $row = $b->get()->first();

        return $row ?? (object) [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0,
            'alpa' => 0, 'bdr' => 0, 'tanpa' => 0, 'total' => 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Riwayat entri absen oleh guru
    // ─────────────────────────────────────────────────────────────────────────

    // TODO: tabel master_kelas, master_mapel belum ada migrasinya
    public function getRiwayatGuru(int $idGuru, int $limit = 20): array
    {
        return DB::table('presensi_siswa as ps')
            ->selectRaw('
                ps.tgl, ps.jam, ps.id_kelas, mk.nama_kelas, mm.nama_mapel,
                COUNT(ps.id) AS total_siswa,
                SUM(CASE WHEN ps.status = "HADIR" THEN 1 ELSE 0 END) AS hadir
            ')
            ->join('master_kelas as mk', 'mk.id_kelas', '=', 'ps.id_kelas')
            ->leftJoin('master_mapel as mm', 'mm.id_mapel', '=', 'ps.id_mapel')
            ->where('ps.id_guru', $idGuru)
            ->groupBy('ps.tgl', 'ps.id_kelas', 'ps.id_mapel', 'mk.nama_kelas', 'mm.nama_mapel')
            ->orderBy('ps.tgl', 'DESC')
            ->limit($limit)
            ->get()->all();
    }
}

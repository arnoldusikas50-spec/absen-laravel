<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\MasterModel;
use App\Models\PoinSiswaModel;

/**
 * Modul Poin Kedisiplinan Siswa (admin).
 * Skor awal 100, pelanggaran mengurangi, penghargaan menambah.
 * Pencatatan manual HANYA dari master disiplin_kategori.
 */
class PoinSiswaController extends BaseController
{
    protected $db;
    protected MasterModel $masterModel;
    protected DashboardModel $dashboardModel;
    protected PoinSiswaModel $poinModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Illuminate\Support\Facades\DB::connection();
        $this->masterModel = new MasterModel();
        $this->dashboardModel = new DashboardModel();
        $this->poinModel = new PoinSiswaModel();
        $this->poinModel->ensureTables();
    }

    /**
     * Leaderboard + kelola kategori.
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first();
        $smt = \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first();
        $setting = $this->getSettingRow();

        $idKelas = (int) ($this->request->query('kelas') ?? 0);
        $kelasList = [];
        if ($tp && $smt) {
            try {
                $kelasList = $this->masterModel->getAllKelas((int) $tp->id_tp, (int) $smt->id_smt);
            } catch (\Throwable $e) {
                $kelasList = [];
            }
        }

        $leaderboard = $this->poinModel->getLeaderboard($idKelas ?: null);
        $kategori = $this->poinModel->getKategori(false);

        // Statistik ringkas
        $totalSiswa = count($leaderboard);
        $skorList = array_map(fn($r) => (int) ($r->skor ?? PoinSiswaModel::SKOR_AWAL), $leaderboard);
        $avg = $totalSiswa ? array_sum($skorList) / $totalSiswa : PoinSiswaModel::SKOR_AWAL;
        $diBawah75 = count(array_filter($skorList, fn($s) => $s < 75));
        try {
            $kasusBulanIni = (int) $this->db->table('poin_siswa_history')
                ->whereRaw('MONTH(created_at) = ?', [date('n')])
                ->whereRaw('YEAR(created_at) = ?', [date('Y')])
                ->count();
        } catch (\Throwable $e) {
            $kasusBulanIni = 0;
        }

        return view('admin.poin_siswa.index', [
            'user' => $this->getUser(),
            'judul' => 'Poin Disiplin Siswa',
            'subjudul' => 'Poin Kedisiplinan Siswa',
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tp' => \Illuminate\Support\Facades\DB::table('master_tp')->orderBy('id_tp', 'DESC')->get(),
            'smt' => \Illuminate\Support\Facades\DB::table('master_smt')->get(),
            'kelas_list' => $kelasList,
            'filter_kelas' => $idKelas,
            'leaderboard' => $leaderboard,
            'kategori' => $kategori,
            'stats' => [
                'total_siswa' => $totalSiswa,
                'rata_rata' => $avg,
                'di_bawah_75' => $diBawah75,
                'kasus_bulan_ini' => $kasusBulanIni,
            ],
        ]);
    }

    /**
     * Detail satu siswa: skor + grafik bulanan + riwayat.
     */
    public function detail($idSiswa)
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isAdmin() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $idSiswa = (int) $idSiswa;
        $siswa = $this->masterModel->getSiswaById($idSiswa);
        if (!$siswa) {
            return redirect()->to('admin/poin-siswa')->with('error', 'Siswa tidak ditemukan');
        }

        $tahun = (int) ($this->request->query('tahun') ?? date('Y'));
        $tahun = max(2020, min($tahun, (int) date('Y') + 1));

        return view('admin.poin_siswa.detail', [
            'user' => $this->getUser(),
            'judul' => 'Detail Poin Siswa',
            'setting' => $this->getSettingRow(),
            'tp_active' => \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first(),
            'smt_active' => \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first(),
            'siswa' => $siswa,
            'skor' => $this->poinModel->getSkor($idSiswa),
            'tahun' => $tahun,
            'monthly' => $this->poinModel->getMonthly($idSiswa, $tahun),
            'type_summary' => $this->poinModel->getTypeSummary($idSiswa, $tahun),
            'riwayat' => $this->poinModel->getRiwayat($idSiswa, 100),
            'kategori' => $this->poinModel->getKategori(true),
        ]);
    }

    /**
     * Catat poin manual (admin & guru piket via endpoint guru).
     * HANYA kategori master aktif.
     */
    public function record()
    {
        if (!$this->isLoggedIn() || (!$this->isAdmin() && !$this->isGuru())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $res = $this->poinModel->recordManual(
            (int) $this->request->input('id_siswa'),
            (int) $this->request->input('kategori_id'),
            (string) ($this->request->input('keterangan') ?? ''),
            $this->getUserId()
        );
        return $this->respond($res['status'], $res['message'] ?? '', $res['skor'] ?? null);
    }

    /**
     * CRUD master kategori (admin saja).
     */
    public function saveKategori()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $id = ($this->request->input('id') ?? '') !== '' ? (int) $this->request->input('id') : null;
        $res = $this->poinModel->saveKategori([
            'nama' => (string) ($this->request->input('nama') ?? ''),
            'jenis' => (string) ($this->request->input('jenis') ?? ''),
            'bobot' => (int) ($this->request->input('bobot') ?? 0),
            'is_active' => (int) ($this->request->input('is_active') ?? 1),
        ], $id);
        return $this->respond($res['status'], $res['message'] ?? '');
    }

    public function deleteKategori()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $res = $this->poinModel->deleteKategori((int) $this->request->input('id'));
        return $this->respond($res['status'], $res['message'] ?? '');
    }

    /**
     * Riwayat JSON untuk modal (limit 10–100).
     */
    public function getHistory($idSiswa)
    {
        if (!$this->isLoggedIn() || (!$this->isAdmin() && !$this->isGuru())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $limit = (int) ($this->request->query('limit') ?? 50);
        $limit = max(10, min($limit, 100));
        return $this->respond(true, 'OK', [
            'skor' => $this->poinModel->getSkor((int) $idSiswa),
            'history' => $this->poinModel->getRiwayat((int) $idSiswa, $limit),
        ]);
    }

    /**
     * Sinkron presensi → poin (terlambat / alpha), idempoten.
     * Menghitung ulang seluruh baris presensi pada periode terpilih supaya
     * keterlambatan yang terlama lalu juga ikut tertangkap, sekaligus
     * mencabut pengurangan yang tak lagi berlaku.
     */
    public function syncPresensi()
    {
        if (!$this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $bulan = (int) ($this->request->input('bulan') ?? date('n'));
        $tahun = (int) ($this->request->input('tahun') ?? date('Y'));
        $idKelas = (int) ($this->request->input('id_kelas') ?? 0);
        if ($bulan < 1 || $bulan > 12 || $tahun < 2020 || $tahun > (int) date('Y') + 1) {
            return $this->respond(false, 'Bulan/tahun tidak valid');
        }

        // Job yang sama dengan /cron/poin-siswa: dua recalc paralel pada baris
        // presensi yang sama saling menimpa (hapus-lama lalu tulis-baru).
        if (!$this->cobaKunci('absen-cron-poin-siswa')) {
            return $this->respond(false, 'Sinkron poin lain masih berjalan — coba lagi sebentar.');
        }

        try {
            $b = $this->db->table('presensi_siswa as ps')
                ->select('ps.*')
                ->whereRaw('MONTH(ps.tgl) = ?', [$bulan])->whereRaw('YEAR(ps.tgl) = ?', [$tahun]);
            if ($idKelas) {
                $b->where('ps.id_kelas', $idKelas);
            }
            // Sinkron hanya baris milik siswa AKTIF; baris non-AKTIF
            // dilewati (recalc juga membatalkan poin otomatisnya).
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('buku_induk')) {
                    $b->join('buku_induk as bi', 'bi.id_siswa = ps.id_siswa', 'left')
                        ->where(function ($q) { $q->where('bi.status', '1')->$q->orWhere('bi.id_siswa IS NULL', null, false); });
                }
            } catch (\Throwable $e2) {
            }
            $rows = $b->get();
        } catch (\Throwable $e) {
            // Pesan exception berisi SQL + kolom — tidak boleh dikirim ke klien.
            Log::error('[Admin/PoinSiswa::syncPresensi] baca presensi gagal: ' . $e->getMessage());
            return $this->respond(false, 'Gagal membaca data presensi — periksa log aplikasi');
        }
        $berubah = 0;
        $lewat = 0;
        $gagal = 0;
        // Satu tombol sinkron memperbaiki dua hal yang diturunkan dari baris
        // presensi yang sama: poin dan denda. Membiarkan salah satunya tertinggal
        // membuat rekap menampilkan poin baru dengan tagihan lama.
        $denda = new \App\Services\SiswaDendaService();
        foreach ($rows as $r) {
            $res = $this->poinModel->syncPresensi($r);
            if (!($res['status'] ?? false)) {
                $gagal++;
            } elseif (!empty($res['skipped'])) {
                $lewat++;
            } else {
                $berubah++;
            }
        }

        // Denda dihitung per bulan, bukan per baris: satu baca dan beberapa
        // tulis pengganti ribuan SELECT + UPDATE. Cakupannya sengaja seluruh
        // sekolah walau tombol ini menekan kelas tertentu — aturannya sama dan
        // perhitungannya idempoten, sementara filter kelas hanya mempersempit
        // baris poin yang disentuh di atas.
        $dendaHasil = $denda->recalcBulanBerjalan($bulan, $tahun);

        return $this->respond(true, "Sinkron selesai: {$berubah} poin berubah, {$lewat} dilewati/tidak berubah"
            . ', ' . $dendaHasil['diubah'] . ' denda disesuaikan'
            . ($dendaHasil['tanpa_baris'] > 0
                ? ' (' . $dendaHasil['tanpa_baris'] . ' hari alpha tanpa baris presensi tetap tertagih di rekap)'
                : '')
            . ($gagal ? ", {$gagal} gagal (lihat log)" : ''));
    }

    /**
     * Anti-tumpang-tindih dengan cron poin (GET_LOCK pada koneksi yang sama
     * dengan query sinkron). Gagal mengambil kunci bukan kesalahan fatal:
     * kalau mekanisme lock-nya sendiri error, jalan terus — sama seperti
     * pola di CronController.
     */
    private function cobaKunci(string $nama): bool
    {
        try {
            $row = $this->db->select('SELECT GET_LOCK(?, 0) AS l', [$nama])[0] ?? null;
        } catch (\Throwable $e) {
            Log::error("[Admin/PoinSiswa] GET_LOCK({$nama}) gagal: " . $e->getMessage());
            return true;
        }

        if ((int) ($row->l ?? 0) === 1) {
            return true;
        }

        Log::warning("[Admin/PoinSiswa] {$nama} dilewati: proses lain masih memegang lock.");

        return false;
    }
}

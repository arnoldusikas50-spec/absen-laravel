<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Models\DashboardModel;

/**
 * KartuSiswa Controller
 * Modul cetak kartu absen siswa QR Code:
 * - Cetak per kelas (format lembar A4 / grid)
 * - Cetak per siswa (individu)
 * - Preview interaktif dengan live QR code rendering
 */
class KartuSiswaController extends BaseController
{
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardModel = new DashboardModel();
    }

    /** Maksimal kartu yang muat di satu permintaan cetak tanpa membuat browser macet. */
    private const BATAS_CETAK = 500;

    /**
     * Filter ke siswa AKTIF saja (buku_induk.status = '1').
     * Kartu QR siswa LULUS/PINDAH/KELUAR tidak dicetak karena
     * QR mereka ditolak saat scan.
     */
    private function filterSiswaAktif($builder, string $exprSiswa = 'ms.id_siswa'): void
    {
        try {
            $db = \Illuminate\Support\Facades\DB::connection();
            if (!\Illuminate\Support\Facades\Schema::hasTable('buku_induk')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }
        $builder->join('buku_induk as bi_aktif', "bi_aktif.id_siswa = {$exprSiswa}", 'left')
            ->where(function ($q) { $q->where('bi_aktif.status', '1')->$q->orWhere('bi_aktif.id_siswa IS NULL', null, false); });
    }

    /**
     * Kelas yang boleh dilihat guru yang sedang login.
     *   null  = seluruh kelas (admin/panitia — sama seperti sebelum-sebelumnya)
     *   []    = tidak ada (guru tanpa perwalian — kartunya KartuGuru, bukan ini)
     *   [..]  = hanya kelas perwaliannya
     * Sumber: master_kelas.guru_id (wali) + jabatan_guru id_jabatan=4,
     * dipersempit TP/SMT aktif agar wali periode lama tidak ikut membaca.
     *
     * @return ?int[]
     */
    private function idKelasBolehDilihat(): ?array
    {
        if ($this->isAdmin() || $this->isPanitia()) {
            return null;
        }

        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        $ids = [];
        try {
            $db = \Illuminate\Support\Facades\DB::connection();
            $guru = $db->table('master_guru')
                ->select('id_guru')->where('id_user', (int) $user->id)
                ->get()->map(fn($r) => (array) $r)->first();
            $idGuru = (int) ($guru['id_guru'] ?? 0);
            if ($idGuru <= 0) {
                return [];
            }

            $tp  = \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first();
            $smt = \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first();

            // 1. Wali kelas via master_kelas.guru_id
            if (\Illuminate\Support\Facades\Schema::hasTable('master_kelas') && \Illuminate\Support\Facades\Schema::hasColumn('master_kelas', 'guru_id')) {
                $q = $db->table('master_kelas')->select('id_kelas')->where('guru_id', $idGuru);
                if ($tp && \Illuminate\Support\Facades\Schema::hasColumn('master_kelas', 'id_tp')) {
                    $q->where('id_tp', (int) $tp->id_tp);
                }
                if ($smt && \Illuminate\Support\Facades\Schema::hasColumn('master_kelas', 'id_smt')) {
                    $q->where('id_smt', (int) $smt->id_smt);
                }
                foreach ($q->get()->map(fn($r) => (array) $r)->all() as $r) {
                    $ids[] = (int) $r['id_kelas'];
                }
            }

            // 2. Wali via jabatan_guru id_jabatan = 4
            if (\Illuminate\Support\Facades\Schema::hasTable('jabatan_guru') && \Illuminate\Support\Facades\Schema::hasColumn('jabatan_guru', 'id_jabatan')) {
                $q = $db->table('jabatan_guru')->select('id_kelas')
                    ->where('id_guru', $idGuru)->where('id_jabatan', 4);
                if ($tp && \Illuminate\Support\Facades\Schema::hasColumn('jabatan_guru', 'id_tp')) {
                    $q->where('id_tp', (int) $tp->id_tp);
                }
                if ($smt && \Illuminate\Support\Facades\Schema::hasColumn('jabatan_guru', 'id_smt')) {
                    $q->where('id_smt', (int) $smt->id_smt);
                }
                foreach ($q->get()->map(fn($r) => (array) $r)->all() as $r) {
                    $ids[] = (int) $r['id_kelas'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('[KartuSiswa::idKelasBolehDilihat] ' . $e->getMessage());
            // Kegagalan pembacaan scope = tolak, bukan buka semua.
            return [];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * TP/SMT aktif adalah syarat kartu: tanpa itu modul pernah diam-diam
     * memakai id_tp/id_smt = 1 dan mencetak roster periode yang salah.
     *
     * @return array{0:int, 1:int, 2:string} [id_tp, id_smt, catatan]
     */
    private function periodeKartu(): array
    {
        $tpSmt = $this->getTpSmt();
        $idTp  = (int) ($tpSmt->tp->id_tp ?? 0);
        $idSmt = (int) ($tpSmt->smt->id_smt ?? 0);

        $catatan = '';
        if ($idTp <= 0 || $idSmt <= 0) {
            $catatan = 'Tahun Pelajaran / Semester aktif belum ditetapkan, jadi belum ada kartu yang bisa disiapkan. '
                . 'Tentukan periode aktif di menu Data Tahun Ajaran lebih dulu.';
        }

        return [$idTp, $idSmt, $catatan];
    }

    /**
     * Halaman Utama / Preview Kartu Absen Siswa
     */
    public function index()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        // Kartu memuat NIS/NISN/foto seluruh siswa — bukan untuk akun siswa.
        if (!$this->isAdmin() && !$this->isPanitia() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tpSmt  = $this->getTpSmt();
        $tp     = $tpSmt->tp;
        $smt    = $tpSmt->smt;
        [$idTp, $idSmt, $catatan] = $this->periodeKartu();

        $idKelas = (int) ($this->request->query('id_kelas') ?? 0);
        $idSiswa = (int) ($this->request->query('id_siswa') ?? 0);

        // Guru biasa hanya boleh menyentuh kelas perwaliannya (pola KartuGuru:
        // guru non-staff discoping ke datanya sendiri, bukan data semua orang).
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && $idKelas > 0 && !in_array($idKelas, $scopeKelas, true)) {
            $idKelas = 0;
        }

        $db = \Illuminate\Support\Facades\DB::connection();
        $hasBukuInduk = \Illuminate\Support\Facades\Schema::hasTable('buku_induk');
        $hasRfidSiswa = \Illuminate\Support\Facades\Schema::hasColumn('master_siswa', 'nomor_rfid');

        $kelases      = [];
        $siswaOptions = [];
        $siswaList    = [];
        $terpotong    = false;

        if ($catatan === '' && $scopeKelas === []) {
            $catatan = 'Anda tidak memegang wali kelas pada periode aktif, jadi tidak ada kartu siswa yang bisa disiapkan di sini. Kartu Anda sendiri ada di menu Cetak Kartu Guru.';
        }

        if ($catatan === '') {
            // Ambil daftar kelas pada TP/SMT aktif (LEFT JOIN agar kelas kosong tetap tampil).
            // jumlah_siswa = siswa AKTIF saja. Join buku_induk ikut diperiksa karena
            // instalasi tanpa tabel itu membuat query gagal dan ->get() mengembalikan
            // false, yang di mode production langsung fatal di ->getResult().
            try {
                $aktifExpr = $hasBukuInduk
                    ? "(bi_aktif.id_siswa IS NULL OR bi_aktif.status = '1')"
                    : '1 = 1';
                $qKelas = $db->table('master_kelas as mk')
                    ->select("mk.id_kelas, mk.nama_kelas, mk.kode_kelas, "
                        . "COUNT(DISTINCT CASE WHEN {$aktifExpr} THEN ks.id_siswa END) AS jumlah_siswa", false)
                    ->join('kelas_siswa as ks', "ks.id_kelas = mk.id_kelas AND ks.id_tp = {$idTp} AND ks.id_smt = {$idSmt}", 'left');
                if ($hasBukuInduk) {
                    $qKelas->join('buku_induk as bi_aktif', 'bi_aktif.id_siswa = ks.id_siswa', 'left');
                }
                // Wali hanya melihat daftar kelasnya sendiri (scope [] sudah ditolak lebih awal).
                if ($scopeKelas !== null) {
                    $qKelas->whereIn('mk.id_kelas', $scopeKelas);
                }
                // nama_kelas/kode_kelas ikut di GROUP BY: secara fungsional memang
                // bergantung pada PK, tapi hanya mode SQL tertentu yang tahu itu.
                $kelases = $qKelas->groupBy('mk.id_kelas, mk.nama_kelas, mk.kode_kelas')
                    ->orderBy('mk.nama_kelas', 'ASC')
                    ->get();
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::index] daftar kelas gagal dibaca: ' . $e->getMessage());
                $kelases  = [];
                $catatan  = 'Daftar kelas gagal dibaca dari basis data — periksa log aplikasi.';
            }

            // Jika belum pilih kelas tapi ada kelas tersedia, default pilih kelas pertama yang berisi siswa
            if ($idKelas === 0 && !empty($kelases)) {
                foreach ($kelases as $k) {
                    if ((int) ($k->jumlah_siswa ?? 0) > 0) {
                        $idKelas = (int) $k->id_kelas;
                        break;
                    }
                }
                if ($idKelas === 0) $idKelas = (int) $kelases[0]->id_kelas;
            }

            // Ambil daftar siswa untuk dropdown filter jika kelas dipilih — AKTIF saja.
            if ($idKelas > 0) {
                try {
                    $optQ = $db->table('master_siswa as ms')
                        ->select('ms.id_siswa, ms.nama, ms.nis')
                        ->join('kelas_siswa as ks', 'ks.id_siswa = ms.id_siswa')
                        ->where('ks.id_kelas', $idKelas)
                        ->where('ks.id_tp', $idTp)
                        ->where('ks.id_smt', $idSmt);
                    $this->filterSiswaAktif($optQ);
                    $siswaOptions = $optQ->orderBy('ms.nama', 'ASC')
                        ->get();
                } catch (\Throwable $e) {
                    Log::error('[KartuSiswa::index] dropdown siswa kelas#' . $idKelas . ' gagal dibaca: ' . $e->getMessage());
                    $siswaOptions = [];
                }
            }

            // Query data siswa yang akan ditampilkan kartunya — AKTIF saja.
            try {
                $builder = $db->table('master_siswa as ms')
                    ->select('ms.id_siswa, ms.nama, ms.nis, ms.nisn, ms.foto, ms.jenis_kelamin, mk.id_kelas, mk.nama_kelas'
                        . ($hasRfidSiswa ? ', ms.nomor_rfid' : ''))
                    ->join('kelas_siswa as ks', 'ks.id_siswa = ms.id_siswa')
                    ->join('master_kelas as mk', 'mk.id_kelas = ks.id_kelas');
                if ($hasBukuInduk) {
                    $this->filterSiswaAktif($builder);
                }
                $builder->where('ks.id_tp', $idTp)->where('ks.id_smt', $idSmt);

                // Filter id_siswa tidak mempersempit kelas: tanpa whereIn ini,
                // wali bisa menebak id_siswa kelas lain dan tetap membaca kartunya.
                if ($scopeKelas !== null) {
                    $builder->whereIn('ks.id_kelas', $scopeKelas);
                }

                if ($idKelas > 0) {
                    $builder->where('ks.id_kelas', $idKelas);
                }
                if ($idSiswa > 0) {
                    $builder->where('ms.id_siswa', $idSiswa);
                }

                $siswaList = $builder->orderBy('mk.nama_kelas', 'ASC')
                    ->orderBy('ms.nama', 'ASC')
                    ->limit(self::BATAS_CETAK + 1)
                    ->get();
                $terpotong = count($siswaList) > self::BATAS_CETAK;
                if ($terpotong) {
                    $siswaList = array_slice($siswaList, 0, self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::index] daftar kartu siswa gagal dibaca: ' . $e->getMessage());
                $siswaList = [];
                $catatan   = 'Daftar kartu siswa gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        // Format data siswa (QR value & photo)
        foreach ($siswaList as $s) {
            $s->qr_value = self::nomorKartuSiswa($s);
            $s->foto_url = self::resolveFotoUrl($s->foto, $s->nis);
        }

        $setting = $this->getSettingRow();

        // Base path role-aware: guru memakai prefix guru, admin memakai prefix admin
        $isGuru   = $this->isGuru();
        $basePath = $isGuru ? 'guru/presensi-siswa/kartu' : 'admin/presensi/siswa/kartu';

        // Pilihan desain: landscape (dengan foto) | portrait (tanpa foto, QR besar)
        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'landscape')));
        if (!in_array($desain, ['landscape', 'portrait'], true)) $desain = 'landscape';

        $data = [
            'title'         => 'Cetak Kartu Absen Siswa QR',
            'judul'         => 'Cetak Kartu Absen Siswa',
            'subjudul'      => 'Cetak Kartu Berisi QR Code Presensi Siswa per Kelas / Individu',
            'user'          => $user,
            'setting'       => $setting,
            'tp_active'     => $tp,
            'smt_active'    => $smt,
            'kelases'       => $kelases,
            'id_kelas'      => $idKelas,
            'id_siswa'      => $idSiswa,
            'siswa_options' => $siswaOptions,
            'siswa_list'    => $siswaList,
            'terpotong'     => $terpotong,
            'batas_cetak'   => self::BATAS_CETAK,
            'catatan'       => $catatan,
            'logo_url'      => self::resolveLogoUrl($setting),
            'base_path'     => $basePath,
            'is_guru'       => $isGuru,
            'desain'        => $desain,
        ];

        return view('admin.presensi.kartu.index', $data);
    }

    /**
     * Halaman Cetak Siap Print (A4 Grid / Single)
     */
    public function cetak()
    {
        $user = $this->getUser();
        if (!$user) return redirect()->to('auth');
        // Kartu memuat NIS/NISN/foto seluruh siswa — bukan untuk akun siswa.
        if (!$this->isAdmin() && !$this->isPanitia() && !$this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tpSmt  = $this->getTpSmt();
        $tp     = $tpSmt->tp;
        $smt    = $tpSmt->smt;
        [$idTp, $idSmt, $catatan] = $this->periodeKartu();

        $idKelas = (int) ($this->request->query('id_kelas') ?? 0);
        $idSiswa = (int) ($this->request->query('id_siswa') ?? 0);

        // Halaman cetak adalah jalur baca yang sama dengan preview — wali juga
        // dibatasi ke kelasnya, dan tanpa kelas di scope → tidak ada yang dicetak.
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && $idKelas > 0 && !in_array($idKelas, $scopeKelas, true)) {
            $idKelas = 0;
        }

        $db = \Illuminate\Support\Facades\DB::connection();
        $hasBukuInduk = \Illuminate\Support\Facades\Schema::hasTable('buku_induk');
        $hasRfidSiswa = \Illuminate\Support\Facades\Schema::hasColumn('master_siswa', 'nomor_rfid');

        $siswaList = [];
        $terpotong = false;

        if ($catatan === '' && $scopeKelas === []) {
            $catatan = 'Anda tidak memegang wali kelas pada periode aktif, jadi tidak ada kartu siswa yang bisa dicetak dari menu ini. Kartu Anda sendiri ada di menu Cetak Kartu Guru.';
        }

        if ($catatan === '') {
            try {
                $builder = $db->table('master_siswa as ms')
                    ->select('ms.id_siswa, ms.nama, ms.nis, ms.nisn, ms.foto, ms.jenis_kelamin, mk.id_kelas, mk.nama_kelas'
                        . ($hasRfidSiswa ? ', ms.nomor_rfid' : ''))
                    ->join('kelas_siswa as ks', 'ks.id_siswa = ms.id_siswa')
                    ->join('master_kelas as mk', 'mk.id_kelas = ks.id_kelas');
                if ($hasBukuInduk) {
                    $this->filterSiswaAktif($builder);
                }
                $builder->where('ks.id_tp', $idTp)->where('ks.id_smt', $idSmt);

                if ($scopeKelas !== null) {
                    $builder->whereIn('ks.id_kelas', $scopeKelas);
                }

                if ($idKelas > 0) {
                    $builder->where('ks.id_kelas', $idKelas);
                }
                if ($idSiswa > 0) {
                    $builder->where('ms.id_siswa', $idSiswa);
                }

                // Batasi BATAS_CETAK kartu per lembar cetak agar browser tidak macet —
                // satu baris ekstra dibaca supaya pemotongan bisa dilaporkan, bukan diam-diam.
                $siswaList = $builder->orderBy('mk.nama_kelas', 'ASC')
                    ->orderBy('ms.nama', 'ASC')
                    ->limit(self::BATAS_CETAK + 1)
                    ->get();
                $terpotong = count($siswaList) > self::BATAS_CETAK;
                if ($terpotong) {
                    $siswaList = array_slice($siswaList, 0, self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::cetak] daftar kartu siswa gagal dibaca: ' . $e->getMessage());
                $siswaList = [];
                $catatan   = 'Daftar kartu siswa gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        foreach ($siswaList as $s) {
            $s->qr_value = self::nomorKartuSiswa($s);
            $s->foto_url = self::resolveFotoUrl($s->foto, $s->nis);
        }

        $setting = $this->getSettingRow();

        // Nama kelas untuk judul cetak
        $namaKelasPilih = 'Semua Kelas';
        if ($idKelas > 0) {
            try {
                $kRow = $db->table('master_kelas')->select('nama_kelas')->where('id_kelas', $idKelas)->first();
                if ($kRow) $namaKelasPilih = 'Kelas ' . $kRow->nama_kelas;
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::cetak] nama kelas#' . $idKelas . ' gagal dibaca: ' . $e->getMessage());
            }
        }
        if ($idSiswa > 0 && count($siswaList) === 1) {
            $namaKelasPilih .= ' • ' . ($siswaList[0]->nama ?? '');
        }

        // Pilihan desain: landscape (dengan foto) | portrait (tanpa foto, QR besar)
        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'landscape')));
        if (!in_array($desain, ['landscape', 'portrait'], true)) $desain = 'landscape';

        // Halaman cetak: jangan di-cache browser/proxy agar revisi desain langsung tampil
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        $data = [
            'setting'       => $setting,
            'tp_active'     => $tp,
            'smt_active'    => $smt,
            'siswa_list'    => $siswaList,
            'nama_kelas'    => $namaKelasPilih,
            'is_single'     => ($idSiswa > 0 && count($siswaList) === 1),
            'terpotong'     => $terpotong,
            'batas_cetak'   => self::BATAS_CETAK,
            'catatan'       => $catatan,
            'logo_url'      => self::resolveLogoUrl($setting),
            'desain'        => $desain,
        ];

        return view('admin.presensi.kartu.cetak', $data);
    }

    /**
     * AJAX Endpoint: Ambil daftar siswa berdasarkan ID Kelas
     */
    public function getSiswaByKelas(int $idKelas)
    {
        if (!$this->isLoggedIn()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => false, 'message' => 'Unauthorized', 'data' => []
            ]);
        }
        if (!$this->isAdmin() && !$this->isPanitia() && !$this->isGuru()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false, 'message' => 'Akses ditolak', 'data' => []
            ]);
        }
        if ($idKelas <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID kelas tidak valid', 'data' => []]);
        }

        // Endpoint ini mengembalikan nama/NIS seluruh anggota kelas —
        // wali hanya boleh menanyakan kelasnya sendiri.
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && !in_array($idKelas, $scopeKelas, true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false, 'message' => 'Akses ditolak', 'data' => []
            ]);
        }

        [$idTp, $idSmt, $catatan] = $this->periodeKartu();
        if ($catatan !== '') {
            return $this->response->setJSON(['status' => false, 'message' => $catatan, 'data' => []]);
        }

        try {
            $db = \Illuminate\Support\Facades\DB::connection();
            $sq = $db->table('master_siswa as ms')
                ->select('ms.id_siswa, ms.nama, ms.nis')
                ->join('kelas_siswa as ks', 'ks.id_siswa = ms.id_siswa')
                ->where('ks.id_kelas', $idKelas)
                ->where('ks.id_tp', $idTp)
                ->where('ks.id_smt', $idSmt);
            $this->filterSiswaAktif($sq);
            $siswa = $sq->orderBy('ms.nama', 'ASC')
                ->get();
        } catch (\Throwable $e) {
            Log::error('[KartuSiswa::getSiswaByKelas] kelas#' . $idKelas . ' gagal dibaca: ' . $e->getMessage());
            return $this->response->setJSON(['status' => false, 'message' => 'Daftar siswa kelas ini gagal dibaca — periksa log aplikasi.', 'data' => []]);
        }

        return $this->response->setJSON([
            'status' => true,
            'data'   => $siswa
        ]);
    }

    /**
     * Satu nomor untuk semua perangkat scan.
     *
     * Nomor kartu RFID dipakai lebih dulu supaya QR/barcode yang dicetak,
     * chip RFID, dan input USB scanner semuanya membawa nomor yang identik.
     * Bila kartu RFID belum didaftarkan, tetap NIS → NISN → id_siswa seperti
     * sebelumnya. Backend scanner menerima ketiga nomor itu, jadi kartu lama
     * yang masih mencetak NIS tetap valid.
     */
    public static function nomorKartuSiswa(object $row): string
    {
        $rfid = trim((string) ($row->nomor_rfid ?? ''));
        if ($rfid !== '') {
            return $rfid;
        }
        if (!empty($row->nis)) {
            return trim((string) $row->nis);
        }
        if (!empty($row->nisn)) {
            return trim((string) $row->nisn);
        }

        return (string) ($row->id_siswa ?? '');
    }

    /**
     * Helper URL Foto Siswa
     */
    public static function resolveFotoUrl(?string $foto, ?string $nis): string
    {
        $defaultAvatar = url('assets/img/siswa.png');

        if (!empty($foto) && !in_array(strtolower(trim($foto)), ['siswa.png', 'default.png', 'null'], true)) {
            $cleanFoto = ltrim($foto, '/');
            if (file_exists(public_path() . '/' . $cleanFoto)) {
                return url($cleanFoto);
            }
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/' . $cleanFoto)) {
                return url('uploads/foto_siswa/' . $cleanFoto);
            }
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/' . $cleanFoto . '.png')) {
                return url('uploads/foto_siswa/' . $cleanFoto . '.png');
            }
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/' . $cleanFoto . '.jpg')) {
                return url('uploads/foto_siswa/' . $cleanFoto . '.jpg');
            }
        }

        if (!empty($nis)) {
            $cleanNis = trim($nis);
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/_' . $cleanNis . '.png')) {
                return url('uploads/foto_siswa/_' . $cleanNis . '.png');
            }
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/' . $cleanNis . '.png')) {
                return url('uploads/foto_siswa/' . $cleanNis . '.png');
            }
            if (file_exists(public_path() . '/' . 'uploads/foto_siswa/' . $cleanNis . '.jpg')) {
                return url('uploads/foto_siswa/' . $cleanNis . '.jpg');
            }
        }

        return $defaultAvatar;
    }

    /**
     * Helper URL Logo Sekolah
     */
    public static function resolveLogoUrl(?object $setting): string
    {
        $defaultLogo = url('assets/img/logo.png');
        if (!$setting) return $defaultLogo;

        if (!empty($setting->logo_kiri)) {
            $cleanLogo = ltrim($setting->logo_kiri, '/');
            if (file_exists(public_path() . '/' . $cleanLogo)) {
                return url($cleanLogo);
            }
        }
        if (!empty($setting->logo_kanan)) {
            $cleanLogo = ltrim($setting->logo_kanan, '/');
            if (file_exists(public_path() . '/' . $cleanLogo)) {
                return url($cleanLogo);
            }
        }

        return $defaultLogo;
    }
}

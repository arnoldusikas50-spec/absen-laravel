<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Services\KartuSiswaFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * KartuSiswa Controller
 * Modul cetak kartu absen siswa QR Code:
 * - Cetak per kelas (format lembar A4 / grid)
 * - Cetak per siswa (individu)
 * - Preview interaktif dengan live QR code rendering
 */
class KartuSiswaController extends BaseController
{
    /** Maksimal kartu yang muat di satu permintaan cetak tanpa membuat browser macet. */
    private const BATAS_CETAK = 500;

    /**
     * Desain yang bisa dirender halaman cetak (lihat view cetak).
     *
     * @var list<string>
     */
    private const DESAIN_CETAK = ['duplex', 'duplex_portrait'];

    /**
     * Mode tata letak halaman cetak.
     *   lembar      = tata letak lama: sisi depan & belakang berdampingan
     *   bolakbalik  = grid A4 dua tahap (semua depan, lalu semua belakang)
     *   ktp         = cetak langsung ukuran KTP/CR80, satu kartu per halaman
     *
     * @var list<string>
     */
    private const MODE_CETAK = ['lembar', 'bolakbalik', 'ktp'];

    /**
     * Ukuran kertas KTP / kartu ID-1 CR80 dalam milimeter (ISO/IEC 7810).
     * Dipakai mode "ktp" untuk menyetel @page persis seukuran kartu.
     */
    private const KTP_LEBAR_MM = 85.6;

    private const KTP_TINGGI_MM = 54.0;

    /**
     * Filter ke siswa AKTIF saja (buku_induk.status = '1').
     * Kartu QR siswa LULUS/PINDAH/KELUAR tidak dicetak karena
     * QR mereka ditolak saat scan.
     */
    private function filterSiswaAktif($builder, string $exprSiswa = 'ms.id_siswa'): void
    {
        try {
            if (! Schema::hasTable('buku_induk')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }
        $builder->leftJoin('buku_induk as bi_aktif', function ($join) use ($exprSiswa) {
            $join->on('bi_aktif.id_siswa', '=', DB::raw($exprSiswa));
        })
            ->where(function ($q) {
                $q->where('bi_aktif.status', '1')->orWhereNull('bi_aktif.id_siswa');
            });
    }

    /**
     * Kelas yang boleh dilihat guru yang sedang login.
     *   null  = seluruh kelas (admin — sama seperti sebelum-sebelumnya)
     *   []    = tidak ada (guru tanpa perwalian — kartunya KartuGuru, bukan ini)
     *   [..]  = hanya kelas perwaliannya
     * Sumber: master_kelas.guru_id (wali) + jabatan_guru id_jabatan=4,
     * dipersempit TP/SMT aktif agar wali periode lama tidak ikut membaca.
     *
     * @return ?int[]
     */
    private function idKelasBolehDilihat(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        $user = $this->getUser();
        if (! $user) {
            return [];
        }

        $ids = [];
        try {
            $db = DB::connection();
            $guru = $db->table('master_guru')
                ->select('id_guru')->where('id_user', (int) $user->id)
                ->get()->map(fn ($r) => (array) $r)->first();
            $idGuru = (int) ($guru['id_guru'] ?? 0);
            if ($idGuru <= 0) {
                return [];
            }

            $tp = DB::table('master_tp')->where('active', 1)->first();
            $smt = DB::table('master_smt')->where('active', 1)->first();

            // 1. Wali kelas via master_kelas.guru_id
            if (Schema::hasTable('master_kelas') && Schema::hasColumn('master_kelas', 'guru_id')) {
                $q = $db->table('master_kelas')->select('id_kelas')->where('guru_id', $idGuru);
                if ($tp && Schema::hasColumn('master_kelas', 'id_tp')) {
                    $q->where('id_tp', (int) $tp->id_tp);
                }
                if ($smt && Schema::hasColumn('master_kelas', 'id_smt')) {
                    $q->where('id_smt', (int) $smt->id_smt);
                }
                foreach ($q->get()->map(fn ($r) => (array) $r)->all() as $r) {
                    $ids[] = (int) $r['id_kelas'];
                }
            }

            // 2. Wali via jabatan_guru id_jabatan = 4
            if (Schema::hasTable('jabatan_guru') && Schema::hasColumn('jabatan_guru', 'id_jabatan')) {
                $q = $db->table('jabatan_guru')->select('id_kelas')
                    ->where('id_guru', $idGuru)->where('id_jabatan', 4);
                if ($tp && Schema::hasColumn('jabatan_guru', 'id_tp')) {
                    $q->where('id_tp', (int) $tp->id_tp);
                }
                if ($smt && Schema::hasColumn('jabatan_guru', 'id_smt')) {
                    $q->where('id_smt', (int) $smt->id_smt);
                }
                foreach ($q->get()->map(fn ($r) => (array) $r)->all() as $r) {
                    $ids[] = (int) $r['id_kelas'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('[KartuSiswa::idKelasBolehDilihat] '.$e->getMessage());

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
        $idTp = (int) ($tpSmt->tp->id_tp ?? 0);
        $idSmt = (int) ($tpSmt->smt->id_smt ?? 0);

        $catatan = '';
        if ($idTp <= 0 || $idSmt <= 0) {
            $catatan = 'Tahun Pelajaran / Semester aktif belum ditetapkan, jadi belum ada kartu yang bisa disiapkan. '
                .'Tentukan periode aktif di menu Data Tahun Ajaran lebih dulu.';
        }

        return [$idTp, $idSmt, $catatan];
    }

    /**
     * Mode tata letak halaman cetak yang diminta, dengan fallback aman.
     */
    private function modeCetak(): string
    {
        $mode = strtolower(trim((string) ($this->request->query('mode') ?? 'lembar')));

        return in_array($mode, self::MODE_CETAK, true) ? $mode : 'lembar';
    }

    /**
     * Arah balik kertas untuk mode bolak-balik.
     *   long  = flip on long edge (standar inkjet Epson/Canon untuk dokumen portrait)
     *   short = flip on short edge
     */
    private function arahFlip(): string
    {
        $flip = strtolower(trim((string) ($this->request->query('flip') ?? 'long')));

        return in_array($flip, ['long', 'short'], true) ? $flip : 'long';
    }

    /**
     * Berapa kartu yang muat di satu lembar A4.
     *
     * Area cetak A4 dengan margin 8mm×10mm ≈ 190mm × 281mm. Kartu CR80
     * landscape 85.6×54mm muat 2 kolom × 4 baris; portrait 54×85.6mm muat
     * 3 kolom × 3 baris. Dihitung statis (bukan dari ukuran perangkat) supaya
     * hasil cetak sama di semua browser.
     *
     * @return array{cols:int, rows:int, per_sheet:int, portrait:bool}
     */
    private static function konfigurasiGrid(bool $portrait): array
    {
        return $portrait
            ? ['cols' => 3, 'rows' => 3, 'per_sheet' => 9, 'portrait' => true]
            : ['cols' => 2, 'rows' => 4, 'per_sheet' => 8, 'portrait' => false];
    }

    /**
     * Susun ulang satu potongan kartu agar sisi belakang tepat berada di balik
     * sisi depan setelah kertas dibalik printer (cetak bolak-balik).
     *
     *   - Flip tepi panjang: kertas dibalik pada sumbu vertikal, jadi urutan
     *     kolom dibalik sedangkan baris tetap.
     *   - Flip tepi pendek: kertas diputar 180°, jadi kolom DAN baris dibalik.
     *
     * @param  array<int, object>  $potongan
     * @return array<int, object>
     */
    private static function urutanSisiBelakang(array $potongan, int $cols, string $flip): array
    {
        $baris = [];
        foreach (array_chunk($potongan, max(1, $cols)) as $isiBaris) {
            $baris[] = array_reverse($isiBaris);
        }
        if ($flip === 'short') {
            $baris = array_reverse($baris);
        }

        $hasil = [];
        foreach ($baris as $isiBaris) {
            foreach ($isiBaris as $kartu) {
                $hasil[] = $kartu;
            }
        }

        return $hasil;
    }

    /**
     * Pecah daftar kartu menjadi lembar-lembar A4 untuk mode bolak-balik,
     * lengkap dengan urutan sisi belakang yang sudah dicerminkan.
     *
     * @param  array<int, object>  $kartu
     * @param  array{cols:int, rows:int, per_sheet:int, portrait:bool}  $grid
     * @return array<int, array{depan: array<int, object>, belakang: array<int, object>}>
     */
    private static function pecahPerLembar(array $kartu, array $grid, string $flip): array
    {
        $lembar = [];
        foreach (array_chunk($kartu, $grid['per_sheet']) as $potongan) {
            $lembar[] = [
                'depan' => array_values($potongan),
                'belakang' => self::urutanSisiBelakang($potongan, $grid['cols'], $flip),
            ];
        }

        return $lembar;
    }

    /**
     * Halaman Utama / Preview Kartu Absen Siswa
     */
    public function index()
    {
        $user = $this->getUser();
        if (! $user) {
            return redirect()->to('auth');
        }
        // Kartu memuat NIS/NISN/foto seluruh siswa — bukan untuk akun siswa.
        if (! $this->isAdmin() && ! $this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;
        [$idTp, $idSmt, $catatan] = $this->periodeKartu();

        $idKelas = (int) ($this->request->query('id_kelas') ?? 0);
        $idSiswa = (int) ($this->request->query('id_siswa') ?? 0);

        // Guru biasa hanya boleh menyentuh kelas perwaliannya (pola KartuGuru:
        // guru non-staff discoping ke datanya sendiri, bukan data semua orang).
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && $idKelas > 0 && ! in_array($idKelas, $scopeKelas, true)) {
            $idKelas = 0;
        }

        $db = DB::connection();
        $hasBukuInduk = Schema::hasTable('buku_induk');
        $hasRfidSiswa = Schema::hasColumn('master_siswa', 'nomor_rfid');

        $kelases = [];
        $siswaOptions = [];
        $siswaList = [];
        $terpotong = false;

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
                    ->select('mk.id_kelas', 'mk.nama_kelas', 'mk.kode_kelas')
                    ->selectRaw("COUNT(DISTINCT CASE WHEN {$aktifExpr} THEN ks.id_siswa END) AS jumlah_siswa")
                    ->leftJoin('kelas_siswa as ks', function ($join) use ($idTp, $idSmt) {
                        $join->on('ks.id_kelas', '=', 'mk.id_kelas')->where('ks.id_tp', '=', $idTp)->where('ks.id_smt', '=', $idSmt);
                    });
                if ($hasBukuInduk) {
                    $qKelas->leftJoin('buku_induk as bi_aktif', 'bi_aktif.id_siswa', '=', 'ks.id_siswa');
                }
                // Wali hanya melihat daftar kelasnya sendiri (scope [] sudah ditolak lebih awal).
                if ($scopeKelas !== null) {
                    $qKelas->whereIn('mk.id_kelas', $scopeKelas);
                }
                // nama_kelas/kode_kelas ikut di GROUP BY: secara fungsional memang
                // bergantung pada PK, tapi hanya mode SQL tertentu yang tahu itu.
                $kelases = $qKelas->groupBy('mk.id_kelas', 'mk.nama_kelas', 'mk.kode_kelas')
                    ->orderBy('mk.nama_kelas', 'ASC')
                    ->get();
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::index] daftar kelas gagal dibaca: '.$e->getMessage());
                $kelases = [];
                $catatan = 'Daftar kelas gagal dibaca dari basis data — periksa log aplikasi.';
            }

            // Jika belum pilih kelas tapi ada kelas tersedia, default pilih kelas pertama yang berisi siswa
            if ($idKelas === 0 && ! empty($kelases)) {
                foreach ($kelases as $k) {
                    if ((int) ($k->jumlah_siswa ?? 0) > 0) {
                        $idKelas = (int) $k->id_kelas;
                        break;
                    }
                }
                if ($idKelas === 0) {
                    $idKelas = (int) $kelases[0]->id_kelas;
                }
            }

            // Ambil daftar siswa untuk dropdown filter jika kelas dipilih — AKTIF saja.
            if ($idKelas > 0) {
                try {
                    $optQ = $db->table('master_siswa as ms')
                        ->select('ms.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn')
                        ->join('kelas_siswa as ks', 'ks.id_siswa', '=', 'ms.id_siswa')
                        ->where('ks.id_kelas', $idKelas)
                        ->where('ks.id_tp', $idTp)
                        ->where('ks.id_smt', $idSmt);
                    if ($hasBukuInduk) {
                        $this->filterSiswaAktif($optQ);
                    }
                    $siswaOptions = $optQ->orderBy('ms.nama', 'ASC')
                        ->get()
                        ->each(function ($siswa) {
                            [$siswa->id_label, $siswa->id_value] = KartuSiswaFormatter::identitas($siswa);
                        });
                } catch (\Throwable $e) {
                    Log::error('[KartuSiswa::index] dropdown siswa kelas#'.$idKelas.' gagal dibaca: '.$e->getMessage());
                    $siswaOptions = [];
                }
            }

            // Query data siswa yang akan ditampilkan kartunya — AKTIF saja.
            try {
                $builder = $db->table('master_siswa as ms')
                    ->select('ms.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn', 'ms.foto', 'ms.jenis_kelamin', 'ms.tempat_lahir', 'ms.tanggal_lahir', 'ms.alamat', 'mk.id_kelas', 'mk.nama_kelas')
                    ->addSelect($hasRfidSiswa ? ['ms.nomor_rfid'] : [])
                    ->join('kelas_siswa as ks', 'ks.id_siswa', '=', 'ms.id_siswa')
                    ->join('master_kelas as mk', 'mk.id_kelas', '=', 'ks.id_kelas');
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
                $terpotong = $siswaList->count() > self::BATAS_CETAK;
                if ($terpotong) {
                    // get() mengembalikan Collection — array_slice() menolak
                    // Collection sebagai argumen #1, jadi pakai take().
                    $siswaList = $siswaList->take(self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::index] daftar kartu siswa gagal dibaca: '.$e->getMessage());
                $siswaList = [];
                $catatan = 'Daftar kartu siswa gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        // Format data siswa (QR value & photo)
        foreach ($siswaList as $s) {
            $s->qr_value = self::nomorKartuSiswa($s);
            $s->foto_url = self::resolveFotoUrl($s->foto, $s->nis);
        }

        $setting = $this->getSettingRow();

        // Base path role-aware: guru memakai prefix guru, admin memakai prefix admin
        $isGuru = $this->isGuru();
        $basePath = $isGuru ? 'guru/presensi-siswa/kartu' : 'admin/presensi/siswa/kartu';

        // Desain pratinjau hanya 2 sisi: duplex (landscape) | duplex_portrait.
        // Nilai lama 'landscape'/'portrait' diarahkan agar tautan lama tetap aman.
        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'duplex')));
        if (! in_array($desain, self::DESAIN_CETAK, true)) {
            $desain = $desain === 'portrait' ? 'duplex_portrait' : 'duplex';
        }
        // Template latar kartu: vektor (desain bawaan) atau PNG hasil unggahan /settings
        [$template, $kartuBgDepan, $kartuBgBelakang] = self::resolveKartuTemplate(
            $setting,
            $desain,
            $this->request->query('template')
        );

        $data = [
            'judul' => 'Cetak Kartu Absen Siswa',
            'user' => $user,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'kelases' => $kelases,
            'id_kelas' => $idKelas,
            'id_siswa' => $idSiswa,
            'siswa_options' => $siswaOptions,
            'siswa_list' => $siswaList,
            'terpotong' => $terpotong,
            'batas_cetak' => self::BATAS_CETAK,
            'catatan' => $catatan,
            'logo_url' => self::resolveLogoUrl($setting),
            'stampel_url' => self::resolveStampelUrl($setting),
            'base_path' => $basePath,
            'desain' => $desain,
            'template' => $template,
            'kartu_bg_depan' => $kartuBgDepan,
            'kartu_bg_belakang' => $kartuBgBelakang,
            'mode' => $this->modeCetak(),
            'flip' => $this->arahFlip(),
            'grid' => self::konfigurasiGrid($desain === 'duplex_portrait'),
            'ktp_lebar_mm' => self::KTP_LEBAR_MM,
            'ktp_tinggi_mm' => self::KTP_TINGGI_MM,
        ];

        return view('admin.presensi.kartu.index', $data);
    }

    /**
     * Halaman Cetak Siap Print (A4 Grid / Single)
     */
    public function cetak()
    {
        $user = $this->getUser();
        if (! $user) {
            return redirect()->to('auth');
        }
        // Kartu memuat NIS/NISN/foto seluruh siswa — bukan untuk akun siswa.
        if (! $this->isAdmin() && ! $this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;
        [$idTp, $idSmt, $catatan] = $this->periodeKartu();

        $idKelas = (int) ($this->request->query('id_kelas') ?? 0);
        $idSiswa = (int) ($this->request->query('id_siswa') ?? 0);

        // Halaman cetak adalah jalur baca yang sama dengan preview — wali juga
        // dibatasi ke kelasnya, dan tanpa kelas di scope → tidak ada yang dicetak.
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && $idKelas > 0 && ! in_array($idKelas, $scopeKelas, true)) {
            $idKelas = 0;
        }

        $db = DB::connection();
        $hasBukuInduk = Schema::hasTable('buku_induk');
        $hasRfidSiswa = Schema::hasColumn('master_siswa', 'nomor_rfid');

        $siswaList = [];
        $terpotong = false;

        if ($catatan === '' && $scopeKelas === []) {
            $catatan = 'Anda tidak memegang wali kelas pada periode aktif, jadi tidak ada kartu siswa yang bisa dicetak dari menu ini. Kartu Anda sendiri ada di menu Cetak Kartu Guru.';
        }

        if ($catatan === '') {
            try {
                $builder = $db->table('master_siswa as ms')
                    ->select('ms.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn', 'ms.foto', 'ms.jenis_kelamin', 'ms.tempat_lahir', 'ms.tanggal_lahir', 'ms.alamat', 'mk.id_kelas', 'mk.nama_kelas')
                    ->addSelect($hasRfidSiswa ? ['ms.nomor_rfid'] : [])
                    ->join('kelas_siswa as ks', 'ks.id_siswa', '=', 'ms.id_siswa')
                    ->join('master_kelas as mk', 'mk.id_kelas', '=', 'ks.id_kelas');
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
                $terpotong = $siswaList->count() > self::BATAS_CETAK;
                if ($terpotong) {
                    // get() mengembalikan Collection — array_slice() menolak
                    // Collection sebagai argumen #1, jadi pakai take().
                    $siswaList = $siswaList->take(self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::cetak] daftar kartu siswa gagal dibaca: '.$e->getMessage());
                $siswaList = [];
                $catatan = 'Daftar kartu siswa gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        foreach ($siswaList as $s) {
            $s->qr_value = self::nomorKartuSiswa($s);
            $s->foto_url = self::resolveFotoUrl($s->foto, $s->nis);
        }

        $setting = $this->getSettingRow();

        // Nama kelas untuk judul cetak
        $namaKelasPilih = 'Semua Kelas';
        // Satu siswa: kelas diambil dari row-nya sendiri, jadi akurat walau
        // id_kelas tidak ikut dikirim (tanpa ini judulnya salah "Semua Kelas").
        $namaKelasSiswa = count($siswaList) === 1 ? trim((string) ($siswaList[0]->nama_kelas ?? '')) : '';
        if ($namaKelasSiswa !== '') {
            $namaKelasPilih = 'Kelas '.$namaKelasSiswa;
        } elseif ($idKelas > 0) {
            try {
                $kRow = $db->table('master_kelas')->select('nama_kelas')->where('id_kelas', $idKelas)->first();
                if ($kRow) {
                    $namaKelasPilih = 'Kelas '.$kRow->nama_kelas;
                }
            } catch (\Throwable $e) {
                Log::error('[KartuSiswa::cetak] nama kelas#'.$idKelas.' gagal dibaca: '.$e->getMessage());
            }
        }
        if ($idSiswa > 0 && count($siswaList) === 1) {
            $namaKelasPilih .= ' • '.($siswaList[0]->nama ?? '');
        }

        // Pilihan desain: landscape | portrait | duplex (2 sisi PVC) | duplex_portrait
        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'duplex')));
        if (! in_array($desain, ['landscape', 'portrait', 'duplex', 'duplex_portrait'], true)) {
            $desain = 'duplex';
        }

        // Halaman cetak hanya merender desain duplex (2 sisi). Desain legacy
        // 1-sisi hanya ada di preview, jadi dipetakan ke duplex terdekat —
        // tanpa ini lembar cetak jadi kosong padahal "$siswa_list" terisi.
        if (! in_array($desain, self::DESAIN_CETAK, true)) {
            $desain = $desain === 'portrait' ? 'duplex_portrait' : 'duplex';
        }

        // Template latar kartu: vektor (desain bawaan) atau PNG hasil unggahan /settings
        [$template, $kartuBgDepan, $kartuBgBelakang] = self::resolveKartuTemplate(
            $setting,
            $desain,
            $this->request->query('template')
        );

        $mode = $this->modeCetak();
        $flip = $this->arahFlip();

        // Mode bolak-balik: kartu dipecah per lembar A4. Semua sisi depan
        // dicetak lebih dulu, lalu semua sisi belakang pada posisi yang sudah
        // dicerminkan supaya jatuh tepat di balik kartunya saat kertas dibalik.
        $grid = self::konfigurasiGrid($desain === 'duplex_portrait');
        $lembar = $mode === 'bolakbalik' ? self::pecahPerLembar($siswaList->all(), $grid, $flip) : [];

        // Halaman cetak: jangan di-cache browser/proxy agar revisi desain langsung tampil
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        $data = [
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'siswa_list' => $siswaList,
            'nama_kelas' => $namaKelasPilih,
            'base_path' => $this->isGuru() ? 'guru/presensi-siswa/kartu' : 'admin/presensi/siswa/kartu',
            'is_single' => ($idSiswa > 0 && count($siswaList) === 1),
            'terpotong' => $terpotong,
            'batas_cetak' => self::BATAS_CETAK,
            'catatan' => $catatan,
            'logo_url' => self::resolveLogoUrl($setting),
            'stampel_url' => self::resolveStampelUrl($setting),
            'desain' => $desain,
            'template' => $template,
            'kartu_bg_depan' => $kartuBgDepan,
            'kartu_bg_belakang' => $kartuBgBelakang,
            'mode' => $mode,
            'flip' => $flip,
            'grid' => $grid,
            'lembar' => $lembar,
            'ktp_lebar_mm' => self::KTP_LEBAR_MM,
            'ktp_tinggi_mm' => self::KTP_TINGGI_MM,
        ];

        return view('admin.presensi.kartu.cetak', $data);
    }

    /**
     * AJAX Endpoint: Ambil daftar siswa berdasarkan ID Kelas
     */
    public function getSiswaByKelas(int $idKelas)
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => false, 'message' => 'Unauthorized', 'data' => [],
            ]);
        }
        if (! $this->isAdmin() && ! $this->isGuru()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false, 'message' => 'Akses ditolak', 'data' => [],
            ]);
        }
        if ($idKelas <= 0) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID kelas tidak valid', 'data' => []]);
        }

        // Endpoint ini mengembalikan nama/NIS seluruh anggota kelas —
        // wali hanya boleh menanyakan kelasnya sendiri.
        $scopeKelas = $this->idKelasBolehDilihat();
        if ($scopeKelas !== null && ! in_array($idKelas, $scopeKelas, true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false, 'message' => 'Akses ditolak', 'data' => [],
            ]);
        }

        [$idTp, $idSmt, $catatan] = $this->periodeKartu();
        if ($catatan !== '') {
            return $this->response->setJSON(['status' => false, 'message' => $catatan, 'data' => []]);
        }

        try {
            $db = DB::connection();
            $sq = $db->table('master_siswa as ms')
                ->select('ms.id_siswa', 'ms.nama', 'ms.nis', 'ms.nisn')
                ->join('kelas_siswa as ks', 'ks.id_siswa', '=', 'ms.id_siswa')
                ->where('ks.id_kelas', $idKelas)
                ->where('ks.id_tp', $idTp)
                ->where('ks.id_smt', $idSmt);
            $this->filterSiswaAktif($sq);
            $siswa = $sq->orderBy('ms.nama', 'ASC')
                ->get()
                ->each(function ($row) {
                    [$row->id_label, $row->id_value] = KartuSiswaFormatter::identitas($row);
                });
        } catch (\Throwable $e) {
            Log::error('[KartuSiswa::getSiswaByKelas] kelas#'.$idKelas.' gagal dibaca: '.$e->getMessage());

            return $this->response->setJSON(['status' => false, 'message' => 'Daftar siswa kelas ini gagal dibaca — periksa log aplikasi.', 'data' => []]);
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => $siswa,
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
        if (! empty($row->nis)) {
            return trim((string) $row->nis);
        }
        if (! empty($row->nisn)) {
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

        if (! empty($foto) && ! in_array(strtolower(trim($foto)), ['siswa.png', 'default.png', 'null'], true)) {
            $cleanFoto = ltrim($foto, '/');
            if (file_exists(public_path().'/'.$cleanFoto)) {
                return url($cleanFoto);
            }
            if (file_exists(public_path().'/'.'uploads/foto_siswa/'.$cleanFoto)) {
                return url('uploads/foto_siswa/'.$cleanFoto);
            }
            if (file_exists(public_path().'/'.'uploads/foto_siswa/'.$cleanFoto.'.png')) {
                return url('uploads/foto_siswa/'.$cleanFoto.'.png');
            }
            if (file_exists(public_path().'/'.'uploads/foto_siswa/'.$cleanFoto.'.jpg')) {
                return url('uploads/foto_siswa/'.$cleanFoto.'.jpg');
            }
        }

        if (! empty($nis)) {
            $cleanNis = trim($nis);
            if (file_exists(public_path().'/'.'uploads/foto_siswa/_'.$cleanNis.'.png')) {
                return url('uploads/foto_siswa/_'.$cleanNis.'.png');
            }
            if (file_exists(public_path().'/'.'uploads/foto_siswa/'.$cleanNis.'.png')) {
                return url('uploads/foto_siswa/'.$cleanNis.'.png');
            }
            if (file_exists(public_path().'/'.'uploads/foto_siswa/'.$cleanNis.'.jpg')) {
                return url('uploads/foto_siswa/'.$cleanNis.'.jpg');
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
        if (! $setting) {
            return $defaultLogo;
        }

        if (! empty($setting->logo_kiri)) {
            $cleanLogo = ltrim($setting->logo_kiri, '/');
            if (file_exists(public_path().'/'.$cleanLogo)) {
                return url($cleanLogo);
            }
        }
        if (! empty($setting->logo_kanan)) {
            $cleanLogo = ltrim($setting->logo_kanan, '/');
            if (file_exists(public_path().'/'.$cleanLogo)) {
                return url($cleanLogo);
            }
        }

        return $defaultLogo;
    }

    /**
     * Helper URL Cap/Stempel Sekolah (kolom setting.stampel, diatur di /settings).
     * Mengembalikan null bila belum diatur atau file tidak ada, sehingga kartu
     * hanya menampilkan stempel saat sekolah benar-benar mengunggahnya.
     */
    public static function resolveStampelUrl(?object $setting): ?string
    {
        if (! $setting || empty($setting->stampel)) {
            return null;
        }

        $clean = ltrim(str_replace('\\', '/', (string) $setting->stampel), '/');
        if (strpos($clean, '..') !== false) {
            return null;
        }
        if (file_exists(public_path().'/'.$clean)) {
            return url($clean);
        }

        return null;
    }

    /**
     * Helper URL latar kartu PNG (kolom setting.kartu_bg_depan /
     * kartu_bg_belakang, diatur di /settings). Mengembalikan null bila belum
     * diunggah atau filenya tidak ada, sehingga template vektor tetap dipakai.
     */
    public static function resolveKartuBgUrl(?object $setting, string $field): ?string
    {
        if (! $setting || empty($setting->{$field})) {
            return null;
        }

        $clean = ltrim(str_replace('\\', '/', (string) $setting->{$field}), '/');
        if (strpos($clean, '..') !== false) {
            return null;
        }
        if (file_exists(public_path().'/'.$clean)) {
            return url($clean);
        }

        return null;
    }

    /**
     * Template kartu duplex portrait: 'vektor' (desain bawaan) atau 'png'
     * (latar hasil unggahan di /settings). Template PNG hanya aktif bila
     * kedua latar (depan + belakang) tersedia dan desainnya duplex_portrait;
     * selain itu jatuh kembali ke vektor.
     *
     * @return array{0: string, 1: ?string, 2: ?string} [template, url depan, url belakang]
     */
    public static function resolveKartuTemplate(?object $setting, string $desain, ?string $param = null): array
    {
        $bgDepan = self::resolveKartuBgUrl($setting, 'kartu_bg_depan');
        $bgBelakang = self::resolveKartuBgUrl($setting, 'kartu_bg_belakang');

        $template = strtolower(trim((string) ($param ?? 'vektor')));
        if (! in_array($template, ['vektor', 'png'], true)) {
            $template = 'vektor';
        }

        if ($template === 'png' && (! $bgDepan || ! $bgBelakang || $desain !== 'duplex_portrait')) {
            $template = 'vektor';
        }

        return [$template, $bgDepan, $bgBelakang];
    }
}

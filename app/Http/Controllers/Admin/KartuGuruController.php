<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Services\AvatarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * KartuGuru Controller
 * Modul cetak kartu absen Guru / GTK QR Code:
 * - Cetak semua guru (format lembar A4 / grid)
 * - Cetak per guru (individu)
 * - Preview interaktif dengan live QR code rendering (Landscape / Portrait)
 * - Kartu mandiri guru (index/cetak dengan role guru, hanya kartu sendiri)
 */
class KartuGuruController extends BaseController
{
    /** Maksimal kartu yang muat di satu permintaan cetak tanpa membuat browser macet. */
    private const BATAS_CETAK = 500;

    /**
     * Kolom identitas yang dipakai kartu. Kolom hasil migrasi terbaru ikut
     * didaftarkan di sini; kolomKartu() menyaringnya sesuai yang ada di tabel.
     */
    private const KOLOM_KARTU = [
        'id_guru', 'id_user', 'nama_guru', 'nip', 'kode_guru', 'nuptk', 'jenis_ptk',
        'tgs_tambahan', 'status_pegawai', 'jenis_kelamin', 'foto', 'username',
        'tempat_lahir', 'tgl_lahir', 'pendidikan', 'nomor_rfid',
    ];

    /**
     * Halaman Utama / Preview Kartu Absen Guru / GTK
     */
    public function index()
    {
        $user = $this->getUser();
        if (! $user) {
            return redirect()->to('auth');
        }
        // Kartu memuat NIP/NUPTK/QR seluruh guru — bukan untuk akun siswa.
        if (! $this->isAdmin() && ! $this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $isStaffPenuh = $this->isAdmin();
        $idGuru = (int) ($this->request->query('id_guru') ?? 0);
        if (! $isStaffPenuh) {
            // Guru hanya boleh melihat/mencetak kartunya sendiri.
            $idGuru = $this->currentGuruId((int) $user->id);
            if ($idGuru <= 0) {
                return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
            }
        }
        $db = DB::connection();

        $guruOptions = [];
        $guruList = [];
        $terpotong = false;
        $catatan = '';

        // Ambil daftar guru untuk dropdown filter
        try {
            $guruOptionsQ = $db->table('master_guru')
                ->select('id_guru', 'nama_guru', 'nip', 'kode_guru', 'nuptk', 'jenis_ptk');
            if (! $isStaffPenuh) {
                $guruOptionsQ->where('id_guru', $idGuru);
            }
            $guruOptions = $guruOptionsQ
                ->orderBy('nama_guru', 'ASC')
                ->get();
        } catch (\Throwable $e) {
            Log::error('[KartuGuru::index] daftar guru untuk filter gagal dibaca: '.$e->getMessage());
            $guruOptions = [];
            $catatan = 'Daftar guru gagal dibaca dari basis data — periksa log aplikasi.';
        }

        // Query data guru yang akan ditampilkan
        if ($catatan === '') {
            try {
                $builder = $db->table('master_guru')->select(self::kolomKartu());

                if ($idGuru > 0) {
                    $builder->where('id_guru', $idGuru);
                }

                // BATAS_CETAK + 1 dibaca supaya pemotongan bisa dilaporkan, bukan diam-diam.
                $guruList = $builder->orderBy('nama_guru', 'ASC')
                    ->limit(self::BATAS_CETAK + 1)
                    ->get();
                $terpotong = count($guruList) > self::BATAS_CETAK;
                if ($terpotong) {
                    $guruList = array_slice($guruList, 0, self::BATAS_CETAK);
                }
            } catch (\Throwable $e) {
                Log::error('[KartuGuru::index] daftar kartu guru gagal dibaca: '.$e->getMessage());
                $guruList = [];
                $catatan = 'Daftar kartu guru gagal dibaca dari basis data — periksa log aplikasi.';
            }
        }

        // Format data guru (QR value & photo)
        foreach ($guruList as $g) {
            $g->qr_value = self::nomorKartuGuru($g);
            $g->foto_url = self::resolveFotoUrl($g->foto, $g->nama_guru);
            $g->jabatan = ! empty($g->tgs_tambahan) ? $g->tgs_tambahan : (! empty($g->jenis_ptk) ? $g->jenis_ptk : 'Guru / Tenaga Pendidik');
        }

        $setting = $this->getSettingRow();
        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        // Admin mengelola semua kartu lewat menu admin; guru hanya kartunya
        // sendiri lewat menu "Kartu QR Saya" (route role:guru).
        $basePath = $isStaffPenuh ? 'admin/presensi/guru/kartu' : 'guru/kartu-saya';

        // Pilihan desain: landscape (dengan foto) | portrait (tanpa foto, QR besar)
        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'landscape')));
        if (! in_array($desain, ['landscape', 'portrait'], true)) {
            $desain = 'landscape';
        }

        // Tata letak cetak: grid (lembar A4) | ktp (kertas disetel seukuran kartu CR80).
        $mode = strtolower(trim((string) ($this->request->query('mode') ?? 'grid')));
        if (! in_array($mode, ['grid', 'ktp'], true)) {
            $mode = 'grid';
        }

        $data = [
            'judul' => 'Cetak Kartu Absen Guru / GTK',
            'user' => $user,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'id_guru' => $idGuru,
            'guru_options' => $guruOptions,
            'guru_list' => $guruList,
            'terpotong' => $terpotong,
            'batas_cetak' => self::BATAS_CETAK,
            'catatan' => $catatan,
            'logo_url' => self::resolveLogoUrl($setting),
            'base_path' => $basePath,
            'desain' => $desain,
            'mode' => $mode,
        ];

        return view('admin.presensi.kartu.guru_index', $data);
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
        if (! $this->isAdmin() && ! $this->isGuru()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $isStaffPenuh = $this->isAdmin();
        $idGuru = (int) ($this->request->query('id_guru') ?? 0);
        if (! $isStaffPenuh) {
            $idGuru = $this->currentGuruId((int) $user->id);
            if ($idGuru <= 0) {
                return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
            }
        }
        $db = DB::connection();

        $guruList = [];
        $terpotong = false;
        $catatan = '';

        try {
            $builder = $db->table('master_guru')->select(self::kolomKartu());

            if ($idGuru > 0) {
                $builder->where('id_guru', $idGuru);
            }

            // BATAS_CETAK + 1 dibaca supaya pemotongan bisa dilaporkan, bukan diam-diam.
            $guruList = $builder->orderBy('nama_guru', 'ASC')
                ->limit(self::BATAS_CETAK + 1)
                ->get();
            $terpotong = count($guruList) > self::BATAS_CETAK;
            if ($terpotong) {
                $guruList = array_slice($guruList, 0, self::BATAS_CETAK);
            }
        } catch (\Throwable $e) {
            Log::error('[KartuGuru::cetak] daftar kartu guru gagal dibaca: '.$e->getMessage());
            $guruList = [];
            $catatan = 'Daftar kartu guru gagal dibaca dari basis data — periksa log aplikasi.';
        }

        foreach ($guruList as $g) {
            $g->qr_value = self::nomorKartuGuru($g);
            $g->foto_url = self::resolveFotoUrl($g->foto, $g->nama_guru);
            $g->jabatan = ! empty($g->tgs_tambahan) ? $g->tgs_tambahan : (! empty($g->jenis_ptk) ? $g->jenis_ptk : 'Guru / Tenaga Pendidik');
        }

        $setting = $this->getSettingRow();
        $tpSmt = $this->getTpSmt();
        $tp = $tpSmt->tp;
        $smt = $tpSmt->smt;

        $judulCetak = 'Semua Guru / GTK';
        if ($idGuru > 0 && count($guruList) === 1) {
            $judulCetak = $guruList[0]->nama_guru;
        }

        $desain = strtolower(trim((string) ($this->request->query('desain') ?? 'landscape')));
        if (! in_array($desain, ['landscape', 'portrait'], true)) {
            $desain = 'landscape';
        }

        // Tata letak cetak: grid (lembar A4) | ktp (kertas disetel seukuran kartu CR80).
        $mode = strtolower(trim((string) ($this->request->query('mode') ?? 'grid')));
        if (! in_array($mode, ['grid', 'ktp'], true)) {
            $mode = 'grid';
        }

        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        $data = [
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'guru_list' => $guruList,
            'judul_cetak' => $judulCetak,
            'base_path' => $isStaffPenuh ? 'admin/presensi/guru/kartu' : 'guru/kartu-saya',
            'is_single' => ($idGuru > 0 && count($guruList) === 1),
            'terpotong' => $terpotong,
            'batas_cetak' => self::BATAS_CETAK,
            'catatan' => $catatan,
            'logo_url' => self::resolveLogoUrl($setting),
            'desain' => $desain,
            'mode' => $mode,
        ];

        return view('admin.presensi.kartu.guru_cetak', $data);
    }

    /**
     * Kolom kartu yang benar-benar ada di tabel master_guru.
     *
     * Kolom hasil migrasi terbaru (mis. `pendidikan`) dilewati bila migrasinya
     * belum dijalankan di server ini. Dengan begitu kartu tetap tampil walau
     * satu kolom opsional belum ada, bukan kosong karena SQLSTATE 42S22.
     *
     * @return string[]
     */
    private static function kolomKartu(): array
    {
        return array_values(array_filter(
            self::KOLOM_KARTU,
            static fn (string $kolom): bool => Schema::hasColumn('master_guru', $kolom),
        ));
    }

    /**
     * ID guru milik user yang sedang login.
     */
    private function currentGuruId(int $userId): int
    {
        try {
            $row = DB::connection()->table('master_guru')
                ->select('id_guru')->where('id_user', $userId)->first();
            if ($row && (int) ($row->id_guru ?? 0) > 0) {
                return (int) $row->id_guru;
            }
        } catch (\Throwable $e) {
        }

        return 0;
    }

    /**
     * Satu nomor untuk semua perangkat scan.
     *
     * Nomor kartu RFID dipakai lebih dulu supaya QR/barcode yang dicetak,
     * chip RFID, dan input USB scanner semuanya membawa nomor yang identik.
     * Bila kartu RFID belum didaftarkan, tetap NIP → kode_guru → username →
     * id_guru seperti sebelumnya. Backend scanner menerima semuanya, jadi
     * kartu lama yang masih mencetak NIP tetap valid.
     */
    public static function nomorKartuGuru(object $row): string
    {
        $rfid = trim((string) ($row->nomor_rfid ?? ''));
        if ($rfid !== '') {
            return $rfid;
        }
        if (! empty($row->nip)) {
            return trim((string) $row->nip);
        }
        if (! empty($row->kode_guru)) {
            return trim((string) $row->kode_guru);
        }
        if (! empty($row->username)) {
            return trim((string) $row->username);
        }

        return (string) ($row->id_guru ?? '');
    }

    /**
     * Helper URL Foto Guru — delegasi ke AvatarService supaya semua halaman
     * memakai aturan resolusi yang sama (dan tidak bergantung host luar).
     */
    public static function resolveFotoUrl(?string $foto, ?string $nama): string
    {
        return AvatarService::tampilan($foto, $nama ?: 'Guru', '#1e56c8');
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
}

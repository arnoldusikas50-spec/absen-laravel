<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PresensiAdminController;
use App\Libraries\SuratNomor;
use App\Libraries\Theme;
use App\Models\AttendanceLocationModel;
use App\Models\AttendanceSettingModel;
use App\Models\DashboardModel;
use App\Models\LogModel;
use App\Models\SettingsModel;
use App\Services\OpenWAService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SettingsController extends BaseController
{
    protected $settings;

    protected $dashboard;

    /**
     * Header dump yang dihasilkan aplikasi ini. restore() menolak file tanpa
     * header ini, supaya file SQL arbitrer tidak bisa diselundupkan sebagai
     * "backup" dan dijalankan penuh terhadap database.
     */
    private const DUMP_HEADER = '-- ABSEN-DB-BACKUP v2';

    /** Penanda di akhir dump yang dibuat lengkap. */
    private const DUMP_MARK_LENGKAP = '-- ABSEN-BACKUP-LENGKAP';

    /** Penanda dump tidak lengkap (ditulis di baris pertama file). */
    private const DUMP_MARK_TIDAK_LENGKAP = '# absen:backup-tidak-lengkap';

    /** Awalan nama file yang dikelola halaman ini; file lain (arsip cron, index.html, .htaccess) dilindungi. */
    private const BACKUP_PREFIXES = ['db_backup_', 'files_backup_', 'pre_restore_'];

    public function __construct()
    {
        parent::__construct();
        $this->settings = new SettingsModel;
        $this->dashboard = new DashboardModel;
    }

    public function index()
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (! $this->isAdmin()) {
            abort(404);
        }

        $user = $this->getUser();
        $settingModel = new AttendanceSettingModel;
        $locationModel = new AttendanceLocationModel;
        $db = DB::connection();

        $absenSetting = $settingModel->first();
        if (! $absenSetting) {
            $settingModel->insert([
                'jam_masuk' => '07:30:00',
                'jam_pulang' => '15:00:00',
                'radius_meter' => 100,
            ]);
            $absenSetting = $settingModel->first();
        }

        $locations = $locationModel->get();
        $jadwal = $db->table('absensi_jadwal_kerja')->orderBy('hari', 'ASC')->get();

        $this->ensureNomorSettingColumns();
        SuratNomor::flushCache();

        $appSetting = $this->getSettingRow();
        if (! $appSetting) {
            $appSetting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        $data = [
            'title' => 'Pengaturan & Konfigurasi Sistem',
            'judul' => 'Pengaturan & Konfigurasi Sistem',
            'subjudul' => 'Profil Sekolah & Kebijakan Presensi',
            'user' => $user,
            'setting' => $appSetting,
            'tp' => DB::table('master_tp')->orderBy('id_tp', 'DESC')->get(),
            'tp_active' => DB::table('master_tp')->where('active', 1)->first(),
            'smt' => DB::table('master_smt')->get(),
            'smt_active' => DB::table('master_smt')->where('active', 1)->first(),
            'absenSetting' => $absenSetting,
            'locations' => $locations,
            'jadwal' => $jadwal,
            'theme' => Theme::current(),
            'themePresets' => Theme::presetCards(),
        ];

        return view('admin.presensi.setting', $data);
    }

    public function dbManager()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return redirect()->to('auth');
        }

        $data = [
            'user' => $this->getUser(),
            'judul' => 'Backup dan Restore',
            'subjudul' => 'Backup dan Restore',
        ];

        $data['tp'] = DB::table('master_tp')->orderBy('id_tp', 'DESC')->get();
        $data['tp_active'] = DB::table('master_tp')->where('active', 1)->first();
        $data['smt'] = DB::table('master_smt')->get();
        $data['smt_active'] = DB::table('master_smt')->where('active', 1)->first();
        $data['setting'] = $this->getSettingRow();
        // Arsip backup disimpan di writable/backups (di luar webroot) dan
        // diunduh lewat downloadBackup(), bukan sebagai file publik.
        $backupPath = storage_path('app').'/'.'backups/';
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        $list = [];
        $totalBytes = 0;
        $dbCount = 0;
        $fileCount = 0;
        $arsipCount = 0;
        $lastBackup = null;
        foreach (glob($backupPath.'*') ?: [] as $filepath) {
            if (! is_file($filepath)) {
                continue;
            }
            $filename = basename($filepath);
            if ($filename === 'index.html' || $filename === '.htaccess') {
                continue;
            }
            $info = pathinfo($filename);
            $bytes = (int) filesize($filepath);
            $mtime = (int) filemtime($filepath);
            $totalBytes += $bytes;
            if ($lastBackup === null || $mtime > $lastBackup) {
                $lastBackup = $mtime;
            }
            $lower = strtolower($filename);
            $isSql = str_ends_with($lower, '.sql');
            if (str_starts_with($lower, 'db_backup_')) {
                $kind = 'database';
                $dbCount++;
            } elseif (str_starts_with($lower, 'pre_restore_')) {
                // Snapshot otomatis yang dibuat restore sebelum menimpa data.
                $kind = 'snapshot';
                $dbCount++;
            } elseif (str_starts_with($lower, 'files_backup_')) {
                $kind = 'file';
                $fileCount++;
            } else {
                // Arsip otomatis cron (db_*.sql.gz) dan file lain di folder ini.
                $kind = 'arsip';
                $arsipCount++;
            }

            // Integritas: true = dump lengkap, false = bertanda tidak-lengkap,
            // null = dump lama tanpa header (tidak bisa direstore dari sini).
            $lengkap = null;
            if (($kind === 'database' || $kind === 'snapshot') && $isSql && is_readable($filepath)) {
                $head = (string) file_get_contents($filepath, false, null, 0, 1024);
                if (str_contains($head, 'absen:backup-tidak-lengkap')) {
                    $lengkap = false;
                } elseif (str_starts_with($head, '-- ABSEN-DB-BACKUP')) {
                    $lengkap = true;
                }
            }

            $list[] = [
                'nama' => $info['filename'] ?? $filename,
                'type' => $info['extension'] ?? '',
                'size' => number_to_size($bytes, 2),
                'size_bytes' => $bytes,
                'tgl' => $mtime,
                'src' => $filename,
                'kind' => $kind,
                'is_sql' => $isSql,
                'lengkap' => $lengkap,
            ];
        }
        usort($list, static fn ($a, $b) => $b['tgl'] <=> $a['tgl']);
        $data['list'] = $list;
        $data['stats'] = [
            'total' => count($list),
            'db' => $dbCount,
            'file' => $fileCount,
            'arsip' => $arsipCount,
            'total_bytes' => $totalBytes,
            'total_size' => count($list) > 0 ? number_to_size($totalBytes, 2) : '0 KB',
            'last' => $lastBackup,
        ];

        return view('setting.db', $data);
    }

    /**
     * Field media yang boleh diunggah/dihapus via endpoint ini.
     */
    private const MEDIA_FIELDS = [
        'logo_kanan',
        'logo_kiri',
        'stampel',
        'tanda_tangan',
        'ttdproktor',
        'kodescan',
        'kopsekolah',
        'banner1',
        'banner2',
        'banner3',
    ];

    /**
     * Ubah src (URL/relatif) menjadi path relatif aman di dalam uploads/settings/.
     * Return null bila di luar direktori yang diizinkan (cegah path traversal).
     */
    private function resolveMediaPath(?string $src): ?string
    {
        if ($src === null || $src === '') {
            return null;
        }
        $rel = ltrim(str_replace(url('/'), '', $src), '/');
        $rel = str_replace('\\', '/', $rel);
        if (preg_match('#^https?://#i', $rel)) {
            return null;
        }
        if (strpos($rel, '..') !== false) {
            return null;
        }
        if (strpos($rel, 'uploads/settings/') !== 0) {
            return null;
        }
        $abs = realpath(base_path().'/'.'public/'.$rel);
        $root = realpath(base_path().'/'.'public/uploads/settings');
        if ($abs === false || $root === false || strpos($abs, $root) !== 0 || ! is_file($abs)) {
            return null;
        }
        // base_path() . '/' bisa berakhir '/' (Linux) atau '\' (Windows): potong dari akar
        // yang sudah dibersihkan lalu paksa separator maju agar cocok dengan
        // nilai di kolom settings.
        $out = substr($abs, strlen(rtrim(base_path().'/'.'public/', '/\\')) + 1);
        $out = str_replace('\\', '/', $out);

        return strpos($out, 'uploads/settings/') === 0 ? $out : null;
    }

    public function uploadFile($logo)
    {
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (! in_array($logo, self::MEDIA_FIELDS, true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Field media tidak valid']);
        }

        $file = $this->request->file('logo');
        $validationRule = [
            'logo' => [
                'label' => 'Image File',
                'rules' => 'uploaded[logo]|is_image[logo]|mime_in[logo,image/jpg,image/jpeg,image/gif,image/png,image/webp]|max_size[logo,5120]',
            ],
        ];

        if (! $this->validate($validationRule)) {
            $data = [
                'status' => false,
                'src' => $this->validator->getErrors()['logo'] ?? 'Invalid file',
                'message' => 'Upload failed',
            ];
        } else {
            if ($file->isValid() && ! false /* was: $file->hasMoved() */) {
                $uploadPath = base_path().'/'.'public/uploads/settings/';
                if (! is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Ekstensi dari MIME tervalidasi (jangan percaya nama file client → cegah .php)
                $mimeMap = [
                    'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                    'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
                ];
                $ext = $mimeMap[$file->getMimeType()] ?? null;
                if ($ext === null) {
                    return $this->response->setJSON(['status' => false, 'message' => 'Tipe file tidak didukung']);
                }

                $fileName = $logo.'.'.$ext;

                // Pindahkan file baru DULU, baru bersihkan varian lama.
                // Urutan terbalik (hapus dulu, move tanpa diperiksa) membuat
                // gangguan apa pun pada move() meninggalkan sekolah tanpa logo
                // sama sekali sementara respons tetap berkata "berhasil".
                // CATATAN: move() Laravel/Symfony mengembalikan objek File,
                // BUKAN true seperti CI4 — kegagalan dilempar sebagai exception.
                try {
                    $pindah = $file->move($uploadPath, $fileName);
                } catch (\Throwable $e) {
                    Log::error('[Settings::uploadFile] move '.$fileName.' gagal: '.$e->getMessage());
                    $pindah = false;
                }

                $tujuan = $uploadPath.$fileName;
                if ($pindah === false || ! is_file($tujuan) || (int) @filesize($tujuan) === 0) {
                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Gambar gagal disimpan ke server. Logo lama belum diganti — coba lagi, dan bila berulang periksa izin folder public/uploads/settings.',
                        'csrfName' => '_token',
                        'csrfHash' => csrf_token(),
                    ]);
                }

                // Hapus file lama field ini dengan ekstensi apapun agar tidak menumpuk
                $existen = str_replace('\\', '/', $uploadPath).$fileName;
                foreach ((glob($uploadPath.$logo.'.*') ?: []) as $ef) {
                    $efN = str_replace('\\', '/', $ef);
                    if (is_file($ef) && $efN !== $existen) {
                        @unlink($ef);
                    }
                }

                $relativePath = 'uploads/settings/'.$fileName;

                if ($this->updateSettingRow([$logo => $relativePath]) !== true) {
                    Log::error('[Settings::uploadFile] simpan path '.$relativePath.' ke setting gagal');

                    return $this->response->setJSON([
                        'status' => false,
                        'message' => 'Gambar sudah tersimpan tapi penamaan di pengaturan gagal diperbarui. Logo yang tampil bisa masih yang lama — simpan ulang.',
                        'src' => url($relativePath),
                        'csrfName' => '_token',
                        'csrfHash' => csrf_token(),
                    ]);
                }

                $data = [
                    'status' => true,
                    'src' => url($relativePath),
                    'relative_path' => $relativePath,
                    'filename' => $fileName,
                    'type' => $file->getClientMimeType(),
                    'size' => (int) @filesize($tujuan),
                ];

            } else {
                $data = [
                    'status' => false,
                    'src' => $file->getErrorString(),
                    'message' => 'File move failed',
                ];
            }
        }

        $data['csrfName'] = '_token';
        $data['csrfHash'] = csrf_token();

        return $this->response->setJSON($data);
    }

    public function deleteFile()
    {
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $src = $this->request->input('src');
        $field = $this->request->input('field');

        if (empty($field) || ! in_array($field, self::MEDIA_FIELDS, true)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Field media tidak valid',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $deletedFile = false;
        if (! empty($src)) {
            $rel = $this->resolveMediaPath($src);
            if ($rel !== null && is_file(base_path().'/'.'public/'.$rel)) {
                @unlink(base_path().'/'.'public/'.$rel);
                $deletedFile = true;
            }
        }

        // Hapus juga file fisik yang ada di direktori settings dengan prefix nama field
        $uploadPath = base_path().'/'.'public/uploads/settings/';
        $existing = glob($uploadPath.$field.'.*');
        if (! empty($existing)) {
            foreach ($existing as $oldFile) {
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                    $deletedFile = true;
                }
            }
        }

        // Selalu sinkronkan DB agar kolom media dikosongkan
        $dbTersimpan = $this->updateSettingRow([$field => '']);

        if (! $dbTersimpan) {
            Log::error('[Settings::deleteFile] kosongkan kolom '.$field.' di setting gagal');
        }

        return $this->response->setJSON([
            'status' => $dbTersimpan,
            'message' => $dbTersimpan
                ? ($deletedFile ? 'File media dihapus.' : 'Kolom media dikosongkan (file sudah tidak ada di server).')
                : 'File di server sudah dihapus, tapi datanya di pengaturan gagal dibersihkan. Isi form pengaturan lalu simpan lagi untuk membereskan.',
            'fileDeleted' => $deletedFile,
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Kanal WhatsApp aktif hanya boleh salah satu dari tiga nilai.
     * Nilai tak dikenal turunkan ke 'off' — lebih aman daripada menebak
     * dan diam-diam mengirim ke gateway yang tidak diinginkan.
     */
    private function normalizeWaMode($mode): string
    {
        $mode = strtolower(trim((string) $mode));

        return in_array($mode, ['off', 'openwa', 'fonnte'], true) ? $mode : 'off';
    }

    private function normalizeMediaPath($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $normalized = str_replace(url('/'), '', $path);

        return ltrim($normalized, '/');
    }

    public function saveSetting()
    {
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = $this->request->input();

        // Handle ba_waktu transformation
        $ba_waktu = null;
        if (! empty($input['ba_waktu'])) {
            // Usually datetime-local input sends format like '2025-01-01T12:00'
            // We want to store as 'Y-m-d H:i:s'
            try {
                $dt = new \DateTime($input['ba_waktu']);
                $ba_waktu = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Ignore parsing error or set to null
            }
        }

        // Sanitize phone and postal code
        $telp = isset($input['tlp']) ? preg_replace('/[^0-9]/', '', $input['tlp']) : null;
        $kodepos = isset($input['kode_pos']) ? preg_replace('/[^0-9]/', '', $input['kode_pos']) : null;

        $updateData = [
            'nama_aplikasi' => $input['nama_aplikasi'] ?? '',
            'web' => $input['web'] ?? '',
            'fax' => $input['fax'] ?? '',
            'email' => $input['email'] ?? '',
            'telp' => $telp,
            'kepsek' => $input['kepsek'] ?? '',
            'nip' => $input['nip'] ?? '',
            'proktor' => $input['proktor'] ?? '',
            'nip_proktor' => $input['nip_proktor'] ?? '',
            'tkn_siswa' => $input['tkn_siswa'] ?? '',
            'mode_app' => $input['mode_app'] ?? '',
            'api_key' => $input['api_key'] ?? '',
            'ba_aktif' => $input['ba_aktif'] ?? '0',
            'ba_waktu' => $ba_waktu,
            'jenjang' => $input['jenjang'] ?? '',
            'satuan_pendidikan' => $input['satuan_pendidikan'] ?? '',
            'sekolah' => $input['nama_sekolah'] ?? '',
            'nss' => $input['nss'] ?? '',
            'npsn' => $input['npsn'] ?? '',
            // Penomoran Surat Teguran
            'kode_sekolah' => trim((string) ($input['kode_sekolah'] ?? '')),
            'surat_teguran_format' => SuratNomor::sanitizeTemplate($input['surat_teguran_format'] ?? null),
            'surat_teguran_padding' => $this->normalizeTeguranPadding($input['surat_teguran_padding'] ?? null),
            'alamat' => $input['alamat'] ?? '',
            'desa' => $input['desa'] ?? '',
            'kecamatan' => $input['kec'] ?? '',
            'kota' => $input['kota'] ?? '',
            'provinsi' => $input['provinsi'] ?? '',
            'kode_pos' => $kodepos,
            // Notification Settings
            'telegram_bot_token' => $input['telegram_bot_token'] ?? '',
            'telegram_chat_id' => $input['telegram_chat_id'] ?? '',
            'wa_gateway_url' => $input['wa_gateway_url'] ?? '',
            'wa_token' => $input['wa_token'] ?? '',
            'wa_target_number' => $input['wa_target_number'] ?? '',
            'wa_gateway_mode' => $this->normalizeWaMode($input['wa_gateway_mode'] ?? ''),
            'wa_base_url' => trim((string) ($input['wa_base_url'] ?? '')),
            'wa_api_key' => trim((string) ($input['wa_api_key'] ?? '')),
            'wa_session_name' => trim((string) ($input['wa_session_name'] ?? '')),
            // MikroTik Settings
            'mikrotik_host' => $input['mikrotik_host'] ?? '',
            'mikrotik_port' => $input['mikrotik_port'] ?? '8728',
            'mikrotik_user' => $input['mikrotik_user'] ?? '',
            'mikrotik_pass' => $input['mikrotik_pass'] ?? '',
        ];

        // HANYA perbarui field media jika memang ada di input POST (mencegah form profil menghapus path gambar di DB)
        foreach (self::MEDIA_FIELDS as $mf) {
            if (array_key_exists($mf, $input)) {
                $updateData[$mf] = $this->normalizeMediaPath($input[$mf]);
            }
        }

        $this->ensureNomorSettingColumns();
        $updateData = $this->filterExistingSettingFields($updateData);

        $status = $this->updateSettingRow($updateData);
        SuratNomor::flushCache();
        // Base URL / API Key / nama sesi bisa berubah; resolutionsesi OpenWA
        // di-cache 60 detik dan harus tidak menunjuk gateway lama.
        (new OpenWAService)->flushCache();

        // Status sinkronisasi notifikasi ikut dilaporkan, supaya "Gagal" di
        // halaman profil tidak lagi menyembunyikan fakta bahwa kolom Telegram/WA
        // tidak pernah sampai ke absensi_setting yang dibaca NotificationService.
        $syncNotif = 'tidak_ada_perubahan';

        // Sinkronkan kolom notifikasi ke absensi_setting (sumber tunggal yang
        // dibaca NotificationService). SettingsModel::allowedFields TIDAK memuat
        // kolom-kolom ini sehingga tanpa sync ini konfigurasi Telegram/WA dari
        // halaman Profil Sekolah diam-diam tidak tersimpan.
        $notifKeys = [
            'telegram_bot_token', 'telegram_chat_id',
            'wa_gateway_url', 'wa_token', 'wa_target_number',
            'wa_gateway_mode', 'wa_base_url', 'wa_api_key', 'wa_session_name',
        ];
        $notifData = [];
        foreach ($notifKeys as $k) {
            if (array_key_exists($k, $input)) {
                $v = is_string($input[$k]) ? trim($input[$k]) : $input[$k];
                // Kanal WA disamakan dengan nilai di form: mode tak dikenal
                // turun ke 'off'. Tanpa ini absensi_setting (sumber yang dibaca
                // NotificationService) bisa berisi 'modeAneh' dan menebak
                // gateway mana yang dipakai.
                if ($k === 'wa_gateway_mode') {
                    $v = $this->normalizeWaMode($v);
                }
                $notifData[$k] = $v;
            }
        }
        if ($notifData !== []) {
            try {
                $db = DB::connection();
                if (Schema::hasTable('absensi_setting')) {
                    $sync = [];
                    foreach ($notifData as $k => $v) {
                        if (Schema::hasColumn('absensi_setting', $k)) {
                            $sync[$k] = $v;
                        }
                    }
                    if ($sync !== []) {
                        $mainId = $this->mainAbsensiSettingId($db);
                        if ($mainId > 0) {
                            $tersimpan = $db->table('absensi_setting')->where('id', $mainId)->update($sync);
                        } else {
                            $tersimpan = $db->table('absensi_setting')->insert($sync) !== false;
                        }
                        $syncNotif = $tersimpan === false ? 'gagal' : 'ok';
                    } else {
                        $syncNotif = 'kolom_tidak_ada';
                    }
                } else {
                    $syncNotif = 'tabel_tidak_ada';
                }
            } catch (\Throwable $e) {
                $syncNotif = 'gagal';
                Log::error('[Settings::saveSetting] sync notifikasi gagal: '.$e->getMessage());
            }
        }

        // Dukung pembaruan grace_period jika dikirim ke endpoint ini
        $graceTersimpan = null;
        $dendaTersimpan = null;
        if (isset($input['grace_period'])) {
            $db = DB::connection();
            $gp = (int) $input['grace_period'];
            $mainId = Schema::hasTable('absensi_setting') ? $this->mainAbsensiSettingId($db) : 0;
            if ($mainId > 0) {
                $graceTersimpan = $db->table('absensi_setting')->where('id', $mainId)->update(['grace_period' => $gp]);
            } else {
                $graceTersimpan = false;
                Log::error('[Settings::saveSetting] grace_period tidak disimpan: baris utama absensi_setting tidak ditemukan');
            }

            try {
                (new PresensiAdminController)->doRecalculateDate(date('Y-m-d'));
            } catch (\Throwable $e) {
                Log::error('[Settings::saveSetting] doRecalculateDate error: '.$e->getMessage());
            }
        }

        if (isset($input['denda_alpha_per_hari'])) {
            $db = DB::connection();
            $alphaData = [
                'denda_alpha_per_hari' => (int) $input['denda_alpha_per_hari'],
            ];
            $mainId = Schema::hasTable('absensi_setting') ? $this->mainAbsensiSettingId($db) : 0;
            if ($mainId > 0) {
                $dendaTersimpan = $db->table('absensi_setting')->where('id', $mainId)->update($alphaData);
            } else {
                $dendaTersimpan = false;
                Log::error('[Settings::saveSetting] denda_alpha_per_hari tidak disimpan: baris utama absensi_setting tidak ditemukan');
            }
        }

        if (! $status) {
            Log::error('[Settings::saveSetting] updateSetting gagal');
        }

        $pesan = $status
            ? 'Pengaturan disimpan.'
            : 'Pengaturan GAGAL disimpan — data yang tampil di halaman ini belum tersimpan di database.';
        $catatan = [];
        if ($graceTersimpan === false) {
            $catatan[] = 'batas waktu telat (grace period)';
        }
        if ($dendaTersimpan === false) {
            $catatan[] = 'denda Alpha per hari';
        }
        if (in_array($syncNotif, ['gagal', 'tabel_tidak_ada', 'kolom_tidak_ada'], true)) {
            $catatan[] = 'pengaturan notifikasi Telegram/WhatsApp';
        }
        if ($catatan !== []) {
            $pesan .= ' Bagian yang TIDAK tersimpan: '.implode(', ', $catatan).'.';
        }

        return $this->response->setJSON([
            'status' => $status && $catatan === [],
            'message' => $pesan,
            'notifSync' => $syncNotif,
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Baris utama absensi_setting = id terkecil, konvensi yang sama dengan
     * AttendanceSettingModel::first(). Update tanpa WHERE dulu menyapu semua
     * baris, termasuk duplikat sisa migrate yang berjalan ulang.
     */
    private function mainAbsensiSettingId($db): int
    {
        try {
            $row = $db->table('absensi_setting')->select('id')
                ->orderBy('id', 'ASC')->limit(1)->first();

            return (int) ($row->id ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Simpan preferensi tema tampilan (Theme Customizer).
     */
    public function saveTheme()
    {
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        $input = null;
        if (stripos($this->request->header('Content-Type'), 'application/json') !== false) {
            try {
                $input = $this->request->json()->all();
            } catch (\Throwable $e) {
                $input = null;
            }
        }
        if (! is_array($input)) {
            $input = $this->request->input();
        }

        $preset = strtolower(trim((string) ($input['preset'] ?? Theme::DEFAULT_PRESET)));
        if (! in_array($preset, Theme::presetKeys(), true)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Preset tema tidak dikenal']);
        }

        $custom = Theme::normalizeHex($input['custom'] ?? null);
        if ($preset === 'custom' && $custom === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Warna dasar custom tidak valid']);
        }

        $this->ensureThemeColumns();

        $status = $this->updateSettingRow([
            'theme_preset' => $preset,
            'theme_custom' => $custom,
        ]);

        Theme::flushCache();
        $theme = Theme::resolve($preset, $custom);

        return $this->response->setJSON([
            'status' => (bool) $status,
            'message' => $status ? 'Tema tampilan berhasil disimpan.' : 'Gagal menyimpan tema.',
            'theme' => [
                'preset' => $theme['preset'],
                'custom' => $theme['custom'],
                'label' => $theme['label'],
            ],
        ]);
    }

    /**
     * Pastikan kolom preferensi tema ada (lazy migration).
     */
    private function ensureThemeColumns(): void
    {
        try {
            $db = DB::connection();
            if (! Schema::hasTable('setting')) {
                return;
            }

            $add = [];
            if (! Schema::hasColumn('setting', 'theme_preset')) {
                $add['theme_preset'] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'default'];
            }
            if (! Schema::hasColumn('setting', 'theme_custom')) {
                $add['theme_custom'] = ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'default' => null];
            }
            if ($add !== []) {
                (new CiForge)->addColumn('setting', $add);
            }
        } catch (\Throwable $e) {
            Log::error('[Settings::ensureThemeColumns] '.$e->getMessage());
        }
    }

    /**
     * Pastikan kolom penomoran surat teguran ada (lazy migration, sama seperti
     * patchAbsensiSettingTable di PresensiAdmin). Guard ini menjaga agar
     * instance yang belum menjalankan `php spark migrate` tetap bisa menyimpan.
     */
    private function ensureNomorSettingColumns(): void
    {
        try {
            $db = DB::connection();
            if (! Schema::hasTable('setting')) {
                return;
            }

            $wanted = [
                'kode_sekolah' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'SMAN2.AT'],
                'surat_teguran_format' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => SuratNomor::DEFAULT_FORMAT],
                'surat_teguran_padding' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => SuratNomor::DEFAULT_PADDING],
            ];

            $add = [];
            foreach ($wanted as $col => $def) {
                if (! Schema::hasColumn('setting', $col)) {
                    $add[$col] = $def;
                }
            }
            if ($add !== []) {
                (new CiForge)->addColumn('setting', $add);
            }
        } catch (\Throwable $e) {
            Log::error('[Settings::ensureNomorSettingColumns] '.$e->getMessage());
        }
    }

    /**
     * Buang key yang kolomnya belum tersedia agar update tidak gagal total.
     */
    private function filterExistingSettingFields(array $data): array
    {
        try {
            $db = DB::connection();
            if (! Schema::hasTable('setting')) {
                return $data;
            }
            foreach (array_keys($data) as $key) {
                if (! Schema::hasColumn('setting', $key)) {
                    unset($data[$key]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('[Settings::filterExistingSettingFields] '.$e->getMessage());
        }

        return $data;
    }

    /**
     * Validasi jumlah digit nomor urut (1-6).
     */
    private function normalizeTeguranPadding($value): int
    {
        $pad = (int) $value;
        if ($pad < 1 || $pad > 6) {
            return SuratNomor::DEFAULT_PADDING;
        }

        return $pad;
    }

    /**
     * Backup Database only
     */
    public function backupDb()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $hasil = $this->buatDumpDatabase('db_backup_'.date('Ymd_His').'.sql');

        if (! $hasil['ok']) {
            return $this->response->setJSON([
                'status' => false,
                'message' => $hasil['error'] ?? 'Gagal menulis file backup',
                'file' => $hasil['file'],
                'dilewati' => $hasil['dilewati'],
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $this->catatLog(3, 'Backup database: '.$hasil['file'].' ('.number_to_size($hasil['size'], 2).')');

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Backup database berhasil: '.$hasil['file'],
            'file' => $hasil['file'],
            'size' => $hasil['size'],
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Inti backup database: dump seluruh tabel milik database koneksi ke file
     * .sql di storage/app/backups (di luar webroot). Dipakai backupDb() (tombol
     * manual) dan snapshot pre-restore otomatis, sehingga aturan dump selalu
     * konsisten: header versi, FOREIGN_KEY_CHECKS di matikan/dinyalakan, dan
     * penanda lengkap/tidak-lengkap di dalam file.
     *
     * @return array{ok: bool, file: string, size: int, dilewati: list<string>, error: ?string}
     */
    private function buatDumpDatabase(string $filename): array
    {
        $backupPath = storage_path('app').'/'.'backups/';
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $db = DB::connection();
        // Schema::getTableListing() menggantikan listTables() CI4. Dua hal
        // penting: (1) $schemaQualified=false agar nama tabel polos, bukan
        // "schema.tabel"; (2) schema HARUS diisi nama database koneksi —
        // bila null, Laravel men-list tabel dari SEMUA schema user di server
        // (schema 'absen' lama, 'cms_sekolah', dll.) dan SELECT/DDL akan gagal
        // untuk tabel yang tidak ada di database ini.
        $tables = Schema::getTableListing($db->getDatabaseName(), false);

        $filepath = $backupPath.$filename;
        $sql = self::DUMP_HEADER."\n"
            .'-- Database: '.$db->getDatabaseName()."\n"
            .'-- Dibuat  : '.date('Y-m-d H:i:s')."\n"
            ."-- Jangan edit isi file ini; restore memvalidasi header di atas.\n"
            ."\n"
            ."SET FOREIGN_KEY_CHECKS=0;\n\n";

        $dilewati = [];
        foreach ($tables as $table) {
            if (strpos($table, 'migration') === 0) {
                continue;
            } // skip migration tables
            $result = $db->table($table)->get();
            if ($result === false) {
                // get() mengembalikan false saat query gagal; melanjutkan berarti
                // dump tanpa tabel ini tapi tetap dilaporkan "berhasil".
                Log::error('[Settings::buatDumpDatabase] gagal membaca '.$table);
                $dilewati[] = $table;

                continue;
            }
            $fields = Schema::getColumnListing($table);
            if (empty($fields)) {
                Log::error('[Settings::buatDumpDatabase] tidak bisa membaca kolom '.$table);
                $dilewati[] = $table;

                continue;
            }

            $createQ = $db->select("SHOW CREATE TABLE `$table`");
            $create = $createQ ? ($createQ[0] ?? null) : null;
            $ddl = is_object($create) ? ($create->{'Create Table'} ?? $create->{'Create View'} ?? null) : null;
            if ($ddl === null) {
                Log::error('[Settings::buatDumpDatabase] tidak ada DDL untuk '.$table);
                $dilewati[] = $table;

                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $ddl.";\n\n";

            if ($result->count() > 0) {
                $sql .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                // get() Laravel mengembalikan Collection — iterasi langsung
                // (array_map() menolak Collection sebagai argumen #2).
                foreach ($result as $row) {
                    $row = (array) $row;
                    $values = [];
                    foreach ($fields as $field) {
                        $val = $row[$field] ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            // Selalu diberi kutip. is_numeric() juga benar untuk
                            // string seperti '0611450719200011' (NIP/ponsel/kodepos),
                            // dan tanpa kutip angka itu menjadi literal numerik saat
                            // restore, sehingga semua angka 0 di depan hilang.
                            // PDO::quote() sudah menambahkan kutip sekaligus
                            // meng-escape (pengganti escapeString() CI4).
                            $values[] = $db->getPdo()->quote((string) $val);
                        }
                    }
                    $rows[] = '('.implode(',', $values).')';
                }
                $sql .= implode(",\n", $rows).";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        if ($dilewati === []) {
            $sql .= self::DUMP_MARK_LENGKAP."\n";
        } else {
            // Penanda di baris pertama: restore menolak file bertanda ini, karena
            // "sebagian lengkap" jauh lebih berbahaya daripada tidak ada backup.
            $sql = self::DUMP_MARK_TIDAK_LENGKAP.' tabel='.implode(',', $dilewati)."\n"
                .'# Dibuat '.date('Y-m-d H:i:s')." — jangan dipakai untuk restore.\n\n".$sql;
        }

        if (file_put_contents($filepath, $sql, LOCK_EX) === false) {
            Log::error('[Settings::buatDumpDatabase] gagal menulis '.$filepath);

            return ['ok' => false, 'file' => $filename, 'size' => 0, 'dilewati' => $dilewati, 'error' => 'Gagal menulis file backup (ruang disk/permission?)'];
        }

        return ['ok' => true, 'file' => $filename, 'size' => (int) filesize($filepath), 'dilewati' => $dilewati, 'error' => null];
    }

    /**
     * Audit log aktivitas backup/restore ke tabel logs via LogModel.
     * Kode aktivitas mengikuti konvensi LogModel: 3=create, 5=update, 6=delete.
     */
    private function catatLog(int $aktivitas, string $keterangan): void
    {
        try {
            (new LogModel)->saveLog($aktivitas, substr($keterangan, 0, 240), $this->getUserId());
        } catch (\Throwable $e) {
            Log::error('[Settings::catatLog] '.$e->getMessage());
        }
    }

    /**
     * Selesaikan nama file backup menjadi path absolut di dalam folder
     * storage/app/backups, dengan pengaman berlapis: regex nama file, tolak
     * '..', whitelist awalan nama file, dan realpath harus tetap di dalam
     * folder backup (cegah path traversal dan symlink keluar folder).
     *
     * @param  list<string>|null  $ekstensiDiizinkan  batasi ekstensi (mis. ['.sql'])
     * @param  list<string>|null  $prefiksDiizinkan  batasi awalan nama file
     * @return string|null path absolut bila valid, null bila ditolak
     */
    private function resolveBackupFile(?string $src, ?array $ekstensiDiizinkan = null, ?array $prefiksDiizinkan = null): ?string
    {
        if ($src === null || $src === '' || strlen($src) > 200) {
            return null;
        }
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $src) || str_contains($src, '..')) {
            return null;
        }

        $lower = strtolower($src);
        $lolosPrefiks = false;
        foreach ($prefiksDiizinkan ?? self::BACKUP_PREFIXES as $prefiks) {
            if (str_starts_with($lower, $prefiks)) {
                $lolosPrefiks = true;
                break;
            }
        }
        if (! $lolosPrefiks) {
            return null;
        }

        if ($ekstensiDiizinkan !== null) {
            $lolosEkstensi = false;
            foreach ($ekstensiDiizinkan as $ekstensi) {
                if (str_ends_with($lower, strtolower($ekstensi))) {
                    $lolosEkstensi = true;
                    break;
                }
            }
            if (! $lolosEkstensi) {
                return null;
            }
        }

        $root = realpath(storage_path('app').'/backups');
        if ($root === false) {
            return null;
        }
        $abs = realpath($root.'/'.$src);
        if ($abs === false || ! is_file($abs) || strpos($abs, $root) !== 0) {
            return null;
        }

        return $abs;
    }

    /**
     * Backup File Uploads (public/uploads)
     */
    public function backupData()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        if (! class_exists('ZipArchive')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Ekstensi ZipArchive tidak tersedia']);
        }

        $backupPath = storage_path('app').'/'.'backups/';
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $srcPath = base_path().'/'.'public/uploads/';
        if (! is_dir($srcPath)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Folder uploads tidak ditemukan']);
        }

        $filename = 'files_backup_'.date('Ymd_His').'.zip';
        $filepath = $backupPath.$filename;

        $zip = new ZipArchive;
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gagal membuat file zip']);
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $dimasukkan = 0;
        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($srcPath));
                $zip->addFile($filePath, $relativePath);
                $dimasukkan++;
            }
        }
        // close() mengembalikan false bila arsip gagal ditulis/ditutup (disk penuh,
        // izin berubah). Tanpa pemeriksaan ini admin mendapat pesan "berhasil"
        // sementara file .zip-nya rusak atau kosong.
        if ($zip->close() !== true) {
            @unlink($filepath);

            return $this->response->setJSON([
                'status' => false,
                'message' => 'Backup file gagal ditutup/dirusak — arsip tidak disimpan. Periksa ruang disk folder writable.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        if ($dimasukkan === 0) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada file di public/uploads — backup tidak dibuat.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $this->catatLog(3, 'Backup file uploads: '.$filename.' ('.$dimasukkan.' file, '.number_to_size(filesize($filepath), 2).')');

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Backup file berhasil: '.$filename,
            'file' => $filename,
            'jumlah_file' => $dimasukkan,
            'size' => filesize($filepath),
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Delete all backup files
     */
    public function delBackup()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $backupPath = storage_path('app').'/'.'backups/';
        if (! is_dir($backupPath)) {
            return $this->response->setJSON(['status' => true, 'message' => 'Folder backup tidak ada',
                'csrfName' => '_token', 'csrfHash' => csrf_token()]);
        }

        // Hanya sapu file yang endpoint ini buat sendiri (termasuk snapshot
        // pre-restore). Sebelumnya glob '*.*' menghapus SEMUA isi folder,
        // termasuk arsip malam cron db_*.sql.gz (satu-satunya recovery point
        // otomatis) dan index.html/.htaccess pelindung folder — semua hilang
        // tanpa jejak dan tetap dilaporkan bersih.
        $polaDiizinkan = self::BACKUP_PREFIXES;
        $dilindungi = [];
        $gagalHapus = [];
        $count = 0;
        foreach (glob($backupPath.'*') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }
            $nama = basename($file);
            foreach ($polaDiizinkan as $pola) {
                if (str_starts_with($nama, $pola)) {
                    if (@unlink($file)) {
                        $count++;
                    } else {
                        $gagalHapus[] = $nama;
                    }

                    continue 2;
                }
            }
            $dilindungi[] = $nama;
        }

        if ($count === 0 && $gagalHapus === []) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Tidak ada file backup manual yang bisa dihapus'
                    .(empty($dilindungi) ? '' : '. '.count($dilindungi).' file arsip otomatis dipertahankan.'),
                'dilindungi' => $dilindungi,
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $pesan = "$count file backup dihapus";
        if ($gagalHapus !== []) {
            $pesan .= ', '.count($gagalHapus).' gagal dihapus: '.implode(', ', array_slice($gagalHapus, 0, 5));
        }
        if ($dilindungi !== []) {
            $pesan .= '. '.count($dilindungi).' file arsip otomatis dipertahankan';
        }

        $this->catatLog(6, "Hapus massal backup manual: $count file dihapus".($gagalHapus !== [] ? ', '.count($gagalHapus).' gagal' : ''));

        return $this->response->setJSON([
            'status' => $gagalHapus === [],
            'message' => $pesan,
            'dihapus' => $count,
            'gagal' => $gagalHapus,
            'dilindungi' => $dilindungi,
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Delete single backup file (alias for view)
     */
    public function hapusBackup()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        $src = trim((string) $this->request->input('src'));
        if ($src === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'Nama file tidak boleh kosong']);
        }

        // Hanya file buatan halaman ini (db_backup_/files_backup_/pre_restore_)
        // yang boleh dihapus; arsip cron db_*.sql.gz dan index.html/.htaccess
        // pelindung folder dilindungi whitelist di resolveBackupFile(), yang
        // juga menahan path traversal (realpath harus tetap di folder backups).
        $filepath = $this->resolveBackupFile($src);
        if ($filepath === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'File tidak ditemukan atau tidak boleh dihapus']);
        }

        if (! @unlink($filepath)) {
            Log::error('[Settings::hapusBackup] gagal menghapus '.$filepath);

            return $this->response->setJSON(['status' => false, 'message' => 'File tidak bisa dihapus (permission/owner?)']);
        }

        $this->catatLog(6, 'Hapus file backup: '.$src);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'File backup dihapus',
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Pecah isi dump SQL menjadi daftar statement.
     *
     * explode(';') tidak aman untuk data sekolah: alamat, catatan wali, dan
     * token bisa mengandung tanda titik koma, sehingga statement terpotong di
     * tengah string literal dan restore menulis data yang sudah rusak.
     */
    private function pisahStatement(string $sql): array
    {
        $statements = [];
        $current = '';
        $quote = null;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($quote !== null) {
                $current .= $ch;
                if ($ch === '\\' && $quote !== '`' && $i + 1 < $len) {
                    $current .= $sql[++$i];

                    continue;
                }
                if ($ch === $quote) {
                    if ($quote !== '`' && $i + 1 < $len && $sql[$i + 1] === $quote) {
                        // kutip berganda ('' di dalam ') adalah literal, bukan penutup
                        $current .= $sql[++$i];

                        continue;
                    }
                    $quote = null;
                }

                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $current .= $ch;

                continue;
            }

            if (($ch === '#' || ($ch === '-' && ($sql[$i + 1] ?? '') === '-'))
                && ($ch === '#' || preg_match('/^--(\s|$)/', substr($sql, $i, 3)))) {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $current .= "\n";

                continue;
            }

            if ($ch === '/' && ($sql[$i + 1] ?? '') === '*') {
                $tutup = strpos($sql, '*/', $i + 2);
                $i = $tutup === false ? $len : $tutup + 1;

                continue;
            }

            if ($ch === ';') {
                $trim = trim($current);
                if ($trim !== '') {
                    $statements[] = $trim;
                }
                $current = '';

                continue;
            }

            $current .= $ch;
        }

        $trim = trim($current);
        if ($trim !== '') {
            $statements[] = $trim;
        }

        return $statements;
    }

    /**
     * Restore database from SQL backup
     */
    public function restore()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if (strtolower($this->request->method()) !== 'post') {
            return $this->response->setStatusCode(405);
        }

        // Konfirmasi ketik RESTORE: menahan klik-kecelakaan, klik ganda, dan
        // form autofill; restore menimpa seluruh database sehingga konfirmasi
        // sadar (bukan sekadar tombol OK) wajib.
        if (strtoupper(trim((string) $this->request->input('konfirmasi'))) !== 'RESTORE') {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Konfirmasi diperlukan: ketik RESTORE pada kolom konfirmasi.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        // Throttle: satu restore per 2 menit, supaya spam/klik ganda tidak
        // menghajar server dan admin sempat menyadari restore yang salah kirim.
        $terakhir = (int) Cache::get('absen_restore_terakhir', 0);
        if ($terakhir > 0 && time() - $terakhir < 120) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Restore baru saja dijalankan '.(time() - $terakhir).' detik lalu. Tunggu beberapa saat lagi.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $src = trim((string) $this->request->input('src'));

        // resolveBackupFile() menahan path traversal (realpath), whitelist
        // awalan nama file, dan membatasi ekstensi ke .sql — arsip terkompresi
        // (.gz/.zip) dibaca sebagai teks menghasilkan byte acak yang bisa
        // lolos parser lalu dieksekusi sebagai SQL rusak.
        $filepath = $this->resolveBackupFile($src, ['.sql']);
        if ($filepath === null) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File backup tidak valid atau tidak ditemukan (hanya db_backup_*.sql / pre_restore_*.sql).',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            return $this->response->setJSON(['status' => false, 'message' => 'File backup kosong atau tidak bisa dibaca']);
        }

        // Dump yang dilaporkan tidak lengkap saat dibuat tidak boleh dipakai untuk
        // restore: DROP TABLE di dalamnya akan menyapu data sedangkan tabel yang
        // tadi dilewati tidak ikut dipulihkan.
        if (preg_match('/^'.preg_quote(self::DUMP_MARK_TIDAK_LENGKAP, '/').'/m', $sql)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File ini ditandai sebagai backup TIDAK lengkap saat dibuat. Buat backup baru dulu, lalu restore yang lengkap.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        // Hanya terima dump buatan aplikasi ini (header eksplisit). Dump dari
        // alat lain, atau payload SQL arbitrer yang menyamar sebagai backup,
        // ditolak — buat backup baru dari halaman ini bila perlu.
        if (! str_starts_with(ltrim($sql), self::DUMP_HEADER)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'File ini bukan dump hasil backup aplikasi (header tidak dikenal). Buat backup baru dari halaman ini, lalu restore file tersebut.',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        // Snapshot pre-restore wajib: bila gagal dibuat, restore dibatalkan —
        // lebih baik tidak terjadi apa-apa daripada menimpa data tanpa jalan
        // kembali.
        $snapshot = $this->buatDumpDatabase('pre_restore_'.date('Ymd_His').'.sql');
        if (! $snapshot['ok']) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Restore dibatalkan: gagal membuat snapshot keadaan saat ini ('.($snapshot['error'] ?? 'tidak diketahui').').',
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        // Eksekusi per statement TANPA transaksi pembungkus: MySQL melakukan
        // implicit commit untuk DDL (DROP/CREATE/ALTER), sehingga begin/commit
        // tidak bisa membatalkan apa pun dan hanya memberi ilusi keamanan.
        // Pemulihan dari hasil yang rusak ditangani lewat snapshot pre-restore.
        $pdo = DB::connection()->getPdo();
        $berhasil = 0;
        $gagal = 0;
        $ditolak = 0;
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->pisahStatement($sql) as $stmt) {
            if (! $this->statementDiizinkan($stmt)) {
                // GRANT/CREATE USER/LOAD DATA dll. bukan bagian dump sekolah;
                // menolaknya mencegah privilege/OS diserang lewat file backup.
                $ditolak++;
                Log::warning('[Settings::restore] statement ditolak (di luar whitelist): '.substr($stmt, 0, 120));

                continue;
            }
            try {
                $pdo->exec($stmt);
                $berhasil++;
            } catch (\Throwable $e) {
                // Statement gagal dicatat lalu dilanjutkan: menghentikan di
                // tengah jalan justru meninggalkan database setengah jadi.
                $gagal++;
                Log::error('[Settings::restore] SQL error: '.$e->getMessage());
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        Cache::put('absen_restore_terakhir', time(), 600);

        $ringkasan = 'berhasil '.$berhasil.', gagal '.$gagal.', ditolak '.$ditolak;

        if ($gagal > 0 || $ditolak > 0) {
            $this->catatLog(5, 'Restore TIDAK lengkap dari '.$src.' ('.$ringkasan.')');

            return $this->response->setJSON([
                'status' => false,
                'message' => 'Restore TIDAK lengkap ('.$ringkasan.'). Snapshot keadaan sebelum restore tersimpan sebagai '
                    .$snapshot['file'].'. Periksa log server sebelum mencoba lagi.',
                'snapshot' => $snapshot['file'],
                'csrfName' => '_token',
                'csrfHash' => csrf_token(),
            ]);
        }

        $this->catatLog(5, 'Restore database dari '.$src.' ('.$ringkasan.'), snapshot pre-restore: '.$snapshot['file']);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Restore database berhasil ('.$ringkasan.'). Snapshot otomatis pre-restore: '.$snapshot['file'],
            'snapshot' => $snapshot['file'],
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    /**
     * Whitelist keyword pertama statement yang boleh dieksekusi saat restore.
     * Dump aplikasi hanya berisi statement di daftar ini; keyword seperti
     * GRANT/CREATE USER/LOAD DATA ditolak karena bukan bagian dump sekolah dan
     * berbahaya bila diselundupkan lewat file yang terlihat seperti backup.
     */
    private function statementDiizinkan(string $stmt): bool
    {
        preg_match('/^[A-Za-z]+/', ltrim($stmt), $m);

        return in_array(
            strtoupper($m[0] ?? ''),
            ['SET', 'USE', 'DROP', 'CREATE', 'ALTER', 'INSERT', 'REPLACE', 'DELETE', 'UPDATE', 'TRUNCATE', 'LOCK', 'UNLOCK', 'RENAME', 'START', 'BEGIN', 'COMMIT', 'ROLLBACK', 'ANALYZE', 'OPTIMIZE'],
            true
        );
    }

    /**
     * Download backup file
     */
    public function downloadBackup()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return redirect()->to('auth');
        }

        $src = (string) ($this->request->query('src') ?? '');

        // Unduhan mencakup backup halaman ini + arsip cron db_*.sql.gz;
        // resolveBackupFile() memvalidasi nama, whitelist awalan, dan realpath
        // (anti path traversal).
        $filepath = $this->resolveBackupFile($src, null, ['db_backup_', 'files_backup_', 'pre_restore_', 'db_']);
        if ($filepath === null) {
            abort(404);
        }
        // File backup sering dibuat cron yang berjalan sebagai user lain; kalau
        // ownership/umask-nya tidak bisa dibaca PHP, DownloadResponse akan
        // gagal di tengah stream tanpa pesan yang jelas di log.
        if (! is_readable($filepath)) {
            Log::error('[Settings::downloadBackup] tidak bisa membaca '.$filepath
                .' — cek permission/owner file (dibuat cron sebagai user lain?).');
            abort(404);
        }

        return $this->response->download($filepath, null)->setFileName(basename($filepath));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\DashboardModel;
use App\Models\MasterModel; // Added for Data Manipulation
use App\Models\LogModel; // For Logging

/**
 * Guru Controller - Migrated from CI3
 * Teacher profile and functions
 */
class GuruController extends BaseController
{
    protected DashboardModel $dashboardModel;
    protected MasterModel $masterModel;
    protected LogModel $logModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardModel = new DashboardModel();
        $this->masterModel = new MasterModel();
        $this->logModel = new LogModel();
    }

    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        if (!$this->inGroup('guru')) {
            return redirect()->to('dashboard');
        }

        $tp = \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first();
        $smt = \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first();
        $user = $this->getUser();

        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        
        if (!$guru) {
            return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan');
        }
        
        // Enrich guru data like Dataguru::edit
        $guru = $this->masterModel->getGuruById($guru->id_guru, $tp->id_tp, $smt->id_smt);
        
        // Decode dokumen JSON
        if (!empty($guru->dokumen) && is_string($guru->dokumen)) {
            $guru->dokumen = json_decode($guru->dokumen, true);
        }

        $data = [
            'judul' => 'Profil Guru',
            'subjudul' => 'Data Profil Saya',
            'setting' => $this->getSettingRow(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'guru' => $guru,
            'user' => $user,
            'id_active' => $guru->id_guru // For view compatibility
        ];

        // Prepare Inputs like CI3 / Dataguru::edit
        $data['input_profile'] = [
            (object) ['label' => 'Nama Lengkap', 'name' => 'nama_guru', 'value' => $guru->nama_guru, 'icon' => 'bi bi-person', 'type' => 'text'],
            (object) ['label' => 'Email', 'name' => 'email', 'value' => $guru->email, 'icon' => 'bi bi-envelope', 'type' => 'text'],
            (object) ['label' => 'NIP / NUPTK', 'name' => 'nip', 'value' => $guru->nip, 'icon' => 'bi bi-person-badge', 'type' => 'text'],
            (object) ['label' => 'Jenis Kelamin', 'name' => 'jenis_kelamin', 'value' => $guru->jenis_kelamin, 'icon' => 'bi bi-gender-ambiguous', 'type' => 'text'],
            (object) ['label' => 'No. Handphone', 'name' => 'no_hp', 'value' => $guru->no_hp, 'icon' => 'bi bi-telephone', 'type' => 'number'],
            (object) ['label' => 'Agama', 'name' => 'agama', 'value' => $guru->agama, 'icon' => 'bi bi-person', 'type' => 'text'],
        ];

        $data['input_alamat'] = [
            (object) ['label' => 'NIK', 'name' => 'no_ktp', 'value' => $guru->no_ktp, 'icon' => 'bi bi-person-badge', 'type' => 'number'],
            (object) ['label' => 'Tempat Lahir', 'name' => 'tempat_lahir', 'value' => $guru->tempat_lahir, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Tgl. Lahir', 'name' => 'tgl_lahir', 'value' => $guru->tgl_lahir, 'icon' => 'bi bi-calendar-date', 'type' => 'text'],
            (object) ['label' => 'Alamat', 'name' => 'alamat_jalan', 'value' => $guru->alamat_jalan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kecamatan', 'name' => 'kecamatan', 'value' => $guru->kecamatan, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kota/Kab.', 'name' => 'kabupaten', 'value' => $guru->kabupaten, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Provinsi', 'name' => 'provinsi', 'value' => $guru->provinsi, 'icon' => 'bi bi-geo-alt', 'type' => 'text'],
            (object) ['label' => 'Kode Pos', 'name' => 'kode_pos', 'value' => $guru->kode_pos, 'icon' => 'bi bi-envelope', 'type' => 'number'],
        ];

        $data['input_dokumen'] = [
            (object) ['label' => 'KTP', 'id' => 'ktp_id', 'name' => 'ktp_id', 'type' => 'file'],
            (object) ['label' => 'KK', 'id' => 'kk_id', 'name' => 'kk_id', 'type' => 'file'],
            (object) ['label' => 'Sertifikat', 'id' => 'sertifikat', 'name' => 'serti[]', 'type' => 'file'],
        ];

        return view('members.guru.profil', $data);
    }
    
    /**
     * Save/Update Profile
     */
    public function updateProfil()
    {
        if (!$this->inGroup('guru')) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        
        $user = $this->getUser();
        $existingGuru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        
        if (!$existingGuru) {
             return $this->response->setJSON(['status' => false, 'message' => 'Data guru tidak ditemukan']);
        }
        
        $id_guru = $existingGuru->id_guru; // Force use ID from session context

        // Whitelist kolom profil yang boleh ditulis guru ke barisnya sendiri.
        // Sebelumnya $this->request->input() utuh dikirim ke master_guru,
        // sehingga guru bisa menampanya status/jabatan/id_tp/kolom apa pun.
        $boleh = [
            'nama_guru', 'email', 'nip', 'jenis_kelamin', 'no_hp', 'agama',
            'no_ktp', 'tempat_lahir', 'tgl_lahir', 'alamat_jalan',
            'kecamatan', 'kabupaten', 'provinsi', 'kode_pos',
        ];
        $data = [];
        foreach (array_intersect_key($this->request->input(), array_flip($boleh)) as $key => $val) {
            $data[$key] = is_scalar($val) ? trim((string) $val) : '';
        }

        // Handle Document Uploads
        $files = $this->request->allFiles();
        if ($files) {
            $currentDokumen = json_decode($existingGuru->dokumen ?? '{}', true);
            if (!is_array($currentDokumen)) $currentDokumen = [];

            // KTP/KK/sertifikat adalah dokumen teks-gambar. Tanpa batas ini file
            // .php/.html ikut berpindah ke webroot (nama acak tetap memakai
            // ekstensi asal) dan bisa dieksekusi/di-serve langsung.
            $ekstensiBoleh = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            $mimeBoleh     = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            $maksByte      = 5 * 1024 * 1024;

            $semuaFile = [];
            foreach ($files as $key => $fileInput) {
                if (is_array($fileInput)) {
                    foreach ($fileInput as $file) {
                        $semuaFile[] = [$key, $file];
                    }
                } else {
                    $semuaFile[] = [$key, $fileInput];
                }
            }

            $ditolak = [];
            foreach ($semuaFile as [$key, $file]) {
                if (!$file->isValid() || false /* was: $file->hasMoved() */) {
                    continue;
                }
                $ext  = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
                $mime = (string) $file->guessMimeType();
                if (!in_array($ext, $ekstensiBoleh, true) || !in_array($mime, $mimeBoleh, true) || $file->getSize() > $maksByte) {
                    $ditolak[] = $key;
                }
            }
            if ($ditolak !== []) {
                // Ditolak sebelum ada file yang dipindah — tidak ada state setengah jadi.
                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Dokumen harus PDF/JPG/PNG/GIF maksimal 5 MB — tidak ada yang diunggah (' . implode(', ', array_unique($ditolak)) . ').',
                ]);
            }

            $terunggah = 0;
            $gagalMove = [];
            foreach ($semuaFile as [$key, $file]) {
                if (!$file->isValid() || false /* was: $file->hasMoved() */) {
                    continue;
                }
                $uploadPath = public_path() . '/' . 'uploads/users/' . $user->id . '/documents/';
                if (!is_dir($uploadPath)) {
                    @mkdir($uploadPath, 0775, true);
                }
                $newName = $file->hashName();
                if (!$file->move($uploadPath, $newName)) {
                    $gagalMove[] = $key;
                    continue;
                }
                $relativePath = 'uploads/users/' . $user->id . '/documents/' . $newName;

                if ($key === 'serti') {
                    // Galur sertifikat: banyak file, simpan sebagai daftar.
                    if (!isset($currentDokumen[$key]) || !is_array($currentDokumen[$key])) {
                        $currentDokumen[$key] = [];
                    }
                    $currentDokumen[$key][] = $relativePath;
                } else {
                    $currentDokumen[$key] = $relativePath;
                }
                $terunggah++;
            }

            // Only update dokumen field if files were processed
            // But we must merge with existing data
            if ($terunggah > 0 || $data !== []) {
                $data['dokumen'] = json_encode($currentDokumen);
            }
            if ($gagalMove !== []) {
                Log::error('[Guru::updateProfil] move dokumen gagal untuk: ' . implode(', ', $gagalMove));
            }
        }

        if ($data === []) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada perubahan yang tersimpan.']);
        }

        // Update Data Guru
        $success = $this->updateMasterData('master_guru', $data, 'id_guru', $id_guru);
        
        if ($success) {
            // Update User Table (Email, Name)
            $userUpdate = [];
            if (isset($data['email'])) $userUpdate['email'] = $data['email'];
            if (isset($data['nama_guru'])) {
                $nama = explode(' ', $data['nama_guru']);
                $userUpdate['first_name'] = $nama[0];
                $userUpdate['last_name'] = count($nama) > 1 ? end($nama) : '';
            }
            
            if (!empty($userUpdate)) {
                $db = \Illuminate\Support\Facades\DB::connection();
                $db->table('users')->where('id', $user->id)->update($userUpdate);
            }
            
            $this->saveAuditLog(2, 'Guru mengupdate profilnya sendiri. ID: ' . $id_guru);
            return $this->response->setJSON(['status' => true, 'message' => 'Profil berhasil diupdate']);
        }
        
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal update profil']);
    }

    /**
     * Update Login Credentials (Username/Password)
     */
    public function updateLogin()
    {
        if (!$this->inGroup('guru') || !$this->isLoggedIn()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        
        $user = $this->getUser();
        $username = trim((string) $this->request->input('username'));
        $oldPass = (string) $this->request->input('old');
        $newPass = (string) $this->request->input('new');

        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9_.\-]+$/]|is_unique[users.username,id,' . (int) $user->id . ']'
        ];

        if ($newPass !== '') {
            $rules['new'] = 'required|min_length[6]|max_length[72]';
            $rules['new_confirm'] = 'required|matches[new]';
        }

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => false, 'errors' => $this->validator->getErrors()]);
        }

        $db = \Illuminate\Support\Facades\DB::connection();

        // Username pindah bersama baris master yang tersambung. Selama ini hanya
        // users.username yang berubah, padahal roster guru dijoin lewat username
        // (UsersModel::getUserGuru, MasterModel) — guru kehilangan akunnya dan
        // "aktifkan akun" justru membuatkan akun KEDUA untuk orang yang sama.
        $akun = new \App\Models\AuthModel();
        if ($username !== trim((string) ($user->username ?? ''))) {
            $rename = $akun->renameUser((int) $user->id, $username);
            if (empty($rename['status'])) {
                return $this->response->setJSON([
                    'status'  => false,
                    'message' => $rename['message'] ?? 'Username gagal diganti — tidak ada yang berubah.',
                ]);
            }
        }

        if ($newPass !== '') {
            // Wajib verifikasi password lama; tulis ke kolom `password` (bcrypt)
            // agar konsisten dengan Auth::login() yang memakai password_verify().
            $row = $db->table('users')->where('id', $user->id)->first();
            if (! $row || ! password_verify($oldPass, (string) ($row->password ?? ''))) {
                return $this->response->setJSON(['status' => false, 'message' => 'Password lama salah — password belum diubah.']);
            }

            $tersimpan = $db->table('users')->where('id', $user->id)->update([
                'password' => password_hash($newPass, PASSWORD_BCRYPT),
            ]);
            if ($tersimpan === false) {
                Log::error('[Guru::updateLogin] update users.password gagal: ' . json_encode(['code' => 0, 'message' => '']));

                return $this->response->setJSON([
                    'status'  => false,
                    'message' => 'Password gagal disimpan. Login masih memakai password lama — coba lagi.',
                ]);
            }
        }

        $this->saveAuditLog(2, 'Guru update login info. UserID: ' . $user->id);

        return $this->response->setJSON([
            'status' => true,
            'text'   => $newPass !== ''
                ? 'Username dan password berhasil diupdate'
                : 'Login info berhasil diupdate',
        ]);
    }

    /**
     * Upload Foto Profil
     */
    public function uploadFoto()
    {
        if (!$this->inGroup('guru')) return $this->response->setJSON(['status' => false]);

        $user = $this->getUser();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        if (!$guru) return $this->response->setJSON(['status' => false]);
        $id_guru = $guru->id_guru;

        $validationRule = [
            'foto' => [
                'label' => 'Image File',
                'rules' => 'uploaded[foto]|is_image[foto]|mime_in[foto,image/jpg,image/jpeg,image/gif,image/png]|max_size[foto,2048]',
            ],
        ];

        if (!$this->validate($validationRule)) {
            return $this->response->setJSON(['status' => false, 'error' => $this->validator->getErrors()]);
        }

        $img = $this->request->file('foto');
        if (false /* was: $img->hasMoved() */) {
            return $this->response->setJSON(['status' => false, 'message' => 'File sudah pernah diproses — muat ulang halaman lalu unggah lagi.']);
        }

        $newName = $img->hashName();
        $path = 'uploads/users/' . $user->id . '/profile/';

        if (! is_dir(public_path() . '/' . $path)) {
            mkdir(public_path() . '/' . $path, 0777, true);
        }

        // Simpan yang baru TERLEBIH DAHULU. Urutan lama menghapus foto lama
        // sebelum move dijalankan, jadi bila move gagal (disk penuh / izin
        // tertulis) guru kehilangan fotonya dan path yang tidak pernah ada
        // ikut tersimpan di database.
        if (! $img->move(public_path() . '/' . $path, $newName)) {
            Log::error('[Guru::uploadFoto] move gagal: ' . $img->getError());

            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Foto gagal disimpan di server. Foto lama masih terpakai — coba lagi, dan bila berulang lapor ke pengembang.',
            ]);
        }

        $dbPath = $path . $newName;
        $tertulis = $this->updateMasterData('master_guru', ['foto' => $dbPath], 'id_guru', $id_guru);
        if ($tertulis === false) {
            @unlink(public_path() . '/' . $dbPath);
            Log::error('[Guru::uploadFoto] simpan path foto ke master_guru gagal untuk id_guru ' . $id_guru);

            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Foto gagal ditautkan ke data Anda. Foto lama masih terpakai — coba lagi.',
            ]);
        }

        // Hapus foto lama hanya setelah yang baru pasti tersimpan.
        if ($guru->foto && file_exists(public_path() . '/' . $guru->foto) && strpos($guru->foto, 'default') === false && $guru->foto !== $dbPath) {
            @unlink(public_path() . '/' . $guru->foto);
        }

        // SYNC TO USERS TABLE (For Attendance App Consistency)
        $db = \Illuminate\Support\Facades\DB::connection();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'foto')) {
            $db->table('users')->where('id', $user->id)->update(['foto' => $dbPath]);
        }

        return $this->response->setJSON(['status' => true, 'src' => url($dbPath)]);
    }

    /**
     * Delete Foto Profil
     */
    public function deleteFoto()
    {
        if (!$this->inGroup('guru')) return $this->response->setJSON(['status' => false]);

        $user = $this->getUser();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        if (!$guru) return $this->response->setJSON(['status' => false]);

        if ($guru->foto && file_exists(public_path() . '/' . $guru->foto)) {
             @unlink(public_path() . '/' . $guru->foto);
        }
        
        $this->updateMasterData('master_guru', ['foto' => null], 'id_guru', $guru->id_guru);
        
        // SYNC TO USERS TABLE
        $db = \Illuminate\Support\Facades\DB::connection();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'foto')) {
            $db->table('users')->where('id', $user->id)->update(['foto' => null]);
        }
        
        return $this->response->setJSON(['status' => true]);
    }

    /**
     * Save Signature
     */
    public function saveSignature()
    {
        if (!$this->inGroup('guru')) return $this->response->setJSON(['status' => false]);

        $user = $this->getUser();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        if (!$guru) return $this->response->setJSON(['status' => false]);

        $image = (string) $this->request->input('image');
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $data = base64_decode($image, true);
        // Validasi hasil decode + tanda tangan PNG + ukuran wajar (≤ 2MB).
        if ($data === false || strlen($data) > 2 * 1024 * 1024 || ! str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
            return $this->response->setJSON(['status' => false, 'message' => 'Gambar tanda tangan tidak valid (PNG, maks 2MB)']);
        }

        $fileName = 'ttd_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $guru->nip) . '_' . time() . '.png';
        $path = 'uploads/ttd/';
        
        if (!is_dir(public_path() . '/' . $path)) {
            mkdir(public_path() . '/' . $path, 0777, true);
        }

        // Tulis yang baru TERLEBIH DAHULU; ttd lama baru dihapus setelah tautan
        // DB berpindah, supaya kegagalan tulis tidak menghilangkan tanda tangan.
        if (file_put_contents(public_path() . '/' . $path . $fileName, $data)) {
            $dbPath = $path . $fileName;
            $this->updateMasterData('master_guru', ['ttd' => $dbPath], 'id_guru', $guru->id_guru);

            if ($guru->ttd && file_exists(public_path() . '/' . $guru->ttd) && $guru->ttd !== $dbPath) {
                @unlink(public_path() . '/' . $guru->ttd);
            }

            return $this->response->setJSON(['status' => true, 'src' => url($dbPath), 'message' => 'Tanda tangan berhasil disimpan']);
        }
        
        return $this->response->setJSON(['status' => false, 'message' => 'Gagal menyimpan file']);
    }

    /**
     * Delete Signature
     */
    public function deleteTtd()
    {
        if (!$this->inGroup('guru')) return $this->response->setJSON(['status' => false]);

        $user = $this->getUser();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        if (!$guru) return $this->response->setJSON(['status' => false]);

        if (!empty($guru->ttd) && file_exists(public_path() . '/' . $guru->ttd)) {
            @unlink(public_path() . '/' . $guru->ttd);
        }
        
        $this->updateMasterData('master_guru', ['ttd' => null], 'id_guru', $guru->id_guru);
        return $this->response->setJSON(['status' => true]);
    }
    
    /**
     * Delete Document
     */
    public function deleteDokumen()
    {
        if (!$this->inGroup('guru')) return $this->response->setJSON(['status' => false]);
        
        $src = $this->request->query('src'); // Can be passed via GET or POST
        if (!$src) $src = $this->request->input('src');
        
        if(!$src) return $this->response->setJSON(['status' => false]);

        $user = $this->getUser();
        $guru = $this->dashboardModel->getDataGuruByUserId($user->id, null, null);
        if (!$guru) return $this->response->setJSON(['status' => false]);
        
        // Enrich to get dokumen field
        $guruFull = $this->masterModel->getGuruById($guru->id_guru);
        
        $dokumen = json_decode($guruFull->dokumen ?? '{}', true);
        if (!$dokumen) $dokumen = [];
        
        // HANYA hapus file yang memang tercatat di dokumen guru ini dan berada
        // di bawah uploads/ milik user yang sama. Tanpa batas ini, $src bebas
        // (mis. '../..' dari input) bisa menghapus file apa pun di server.
        $terdaftar = false;
        foreach ($dokumen as $val) {
            if (is_array($val) ? in_array($src, $val, true) : $val === $src) {
                $terdaftar = true;
                break;
            }
        }
        $ownDir = realpath(public_path() . '/' . 'uploads/users/' . $user->id);
        $target = realpath(public_path() . '/' . $src);
        if ($terdaftar && $ownDir !== false && $target !== false && str_starts_with($target, $ownDir . DIRECTORY_SEPARATOR)) {
            @unlink($target);
        }
        
        // Update DB JSON
        $newDokumen = [];
        foreach($dokumen as $key => $val) {
             if (is_array($val)) {
                 $newVals = [];
                 foreach($val as $v) {
                     if ($v !== $src) $newVals[] = $v;
                 }
                 if (!empty($newVals)) $newDokumen[$key] = $newVals;
             } else {
                 if ($val !== $src) $newDokumen[$key] = $val;
             }
        }
        
        $this->updateMasterData('master_guru', ['dokumen' => json_encode($newDokumen)], 'id_guru', $guru->id_guru);
        
        return $this->response->setJSON(['status' => true, 'dokumen' => $newDokumen]);
    }
}

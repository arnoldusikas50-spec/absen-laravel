<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\DashboardModel;
use App\Models\MasterModel;
use App\Models\UsersModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UseradminController extends BaseController
{
    protected $users;

    protected $dashboard;

    protected $auth;

    protected $master;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UsersModel;
        $this->dashboard = new DashboardModel;
        $this->auth = new AuthModel;
        $this->master = new MasterModel;
    }

    public function index()
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (! $this->isAdmin()) {
            return redirect()->to('dashboard');
        }

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'Admin Management',
            'subjudul' => 'Data Admin',
            'profile' => null,
            'setting' => $this->getSettingRow(),
            'tp' => DB::table('master_tp')->orderBy('id_tp', 'DESC')->get(),
            'tp_active' => DB::table('master_tp')->where('active', 1)->first(),
            'smt' => DB::table('master_smt')->get(),
            'smt_active' => DB::table('master_smt')->where('active', 1)->first(),
            'admins' => $this->users->getAllAdmin(),
            'is_admin' => $this->isAdmin(),
        ];

        return view('users.admin.data', $data);
    }

    public function data()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        return $this->response->setJSON($this->users->getAllAdmin());
    }

    public function create()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $rules = [
            'username' => 'required|string|min:4|max:30|alpha_dash',
            'first_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'password' => 'required|string|min:6|max:72',
            'confirm_password' => 'required|same:password',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        } else {
            $username = trim((string) $this->request->input('username'));
            $password = (string) $this->request->input('password');
            $first_name = trim((string) $this->request->input('first_name'));
            $last_name = trim((string) $this->request->input('last_name'));
            $email = trim((string) $this->request->input('email'));

            if ($this->authIsExist($username)) {
                return $this->response->setJSON([
                    'status' => false,
                    'msg' => 'Username tidak tersedia (sudah digunakan).',
                ]);
            } else {
                $user = [
                    'username' => $username,
                    'password' => Hash::make($password),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email !== '' ? $email : null,
                ];
                $id_user = $this->authRegister($user, '1');
                if ($id_user <= 0) {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'Akun gagal dibuat (username baru saja dipakai atau basis data menolak tulisannya).',
                    ]);
                }

                return $this->response->setJSON([
                    'status' => true,
                    'msg' => 'User berhasil dibuat.',
                ]);
            }
        }
    }

    public function edit($id)
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('auth');
        }

        $id = (int) $id;
        // Non-admin hanya boleh membuka halaman edit miliknya sendiri
        if (! $this->isAdmin() && $id !== (int) $this->getUserId()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $target = $this->authUser($id);
        if (! $target) {
            return redirect()->to('useradmin')->with('error', 'User tidak ditemukan');
        }

        $user = $this->getUser();
        $data = [
            'user' => $user,
            'judul' => 'Administrator',
            'subjudul' => 'Edit Data Admin',
            'users' => $target,
            'groups' => $this->authGroups(),
            'level' => $this->isAdmin() ? '1' : '2',
            'setting' => $this->getSettingRow(),
            'tp' => DB::table('master_tp')->orderBy('id_tp', 'DESC')->get(),
            'tp_active' => DB::table('master_tp')->where('active', 1)->first(),
            'smt' => DB::table('master_smt')->get(),
            'smt_active' => DB::table('master_smt')->where('active', 1)->first(),
            'is_admin' => $this->isAdmin(),
        ];

        return view('users.admin.edit', $data);
    }

    public function edit_info()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        if (! $this->validate(['first_name' => 'required|string|max:50'])) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        } else {
            $id = (int) $this->request->input('id');
            if ($id <= 0 || ! $this->authUser($id)) {
                return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
            }
            $input = [
                'first_name' => trim((string) $this->request->input('first_name')),
                'last_name' => trim((string) $this->request->input('last_name')),
                'email' => trim((string) $this->request->input('email')) ?: null,
                'jabatan' => mb_substr(trim((string) $this->request->input('jabatan')), 0, 50) ?: null,
            ];
            $update = $this->updateMasterData('users', $input, 'id', $id);

            return $this->response->setJSON(['status' => (bool) $update]);
        }
    }

    public function edit_status()
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        if (! $this->validate(['status' => 'required|in:0,1'])) {
            return $this->response->setJSON(['status' => false, 'errors' => $this->validator->getErrors()]);
        }
        $id = (int) $this->request->input('id');
        $status = (int) $this->request->input('status');
        if ($id <= 0 || ! $this->authUser($id)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
        }
        // Cegah admin menonaktifkan akunnya sendiri
        if ($id === (int) $this->getUserId() && $status === 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tidak bisa menonaktifkan akun sendiri']);
        }
        $update = $this->updateMasterData('users', ['active' => $status], 'id', $id);

        return $this->response->setJSON(['status' => (bool) $update]);
    }

    public function delete($id)
    {
        if (! $this->isLoggedIn() || ! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        // Hanya izinkan POST (route GET sudah dihapus)
        if (strtoupper($this->request->method()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => false, 'msg' => 'Metode tidak diizinkan']);
        }
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => false, 'msg' => 'ID tidak valid']);
        }
        // Cegah hapus akun sendiri & superadmin utama (id 1)
        if ($id === (int) $this->getUserId()) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Tidak bisa menghapus akun sendiri']);
        }
        if ($id === 1) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akun superadmin utama tidak bisa dihapus']);
        }
        if (! $this->authUser($id)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'User tidak ditemukan']);
        }
        $status = $this->authDeleteUser($id);

        return $this->response->setJSON(['status' => (bool) $status, 'msg' => $status ? 'Admin dihapus' : 'Gagal menghapus']);
    }

    public function uploadFile($id_user)
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $id_user = (int) $id_user;
        if (! $this->isAdmin() && $id_user !== (int) $this->getUserId()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }

        // Batas ukuran & tipe ditegakkan di server: periksa nama ekstensi saja
        // membiarkan .php yang berganti nama jadi .jpg masuk ke webroot.
        $rules = [
            'foto' => [
                'label' => 'Foto profil',
                'rules' => 'required|file|image|mimetypes:image/jpeg,image/gif,image/png,image/webp|max:2048',
            ],
        ];
        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $file = $this->request->file('foto');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada file yang diunggah.']);
        }
        if (false /* was: $file->hasMoved() */) {
            return $this->response->setJSON(['status' => false, 'message' => 'File sudah pernah diproses — muat ulang halaman lalu unggah lagi.']);
        }

        // Ekstensi simpan diturunkan dari MIME asli (sudah disaring mime_in),
        // bukan nama file dari klien.
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $ext = $extMap[(string) $file->getMimeType()] ?? null;
        if ($ext === null) {
            return $this->response->setJSON(['status' => false, 'message' => 'Format file tidak diizinkan. Gunakan JPG, PNG, WEBP, atau GIF.']);
        }

        // Kepemilikan baris diperiksa SEBELUM move: dulu file sudah menimpa foto
        // lama baru diketahui usernya tidak ada, sehingga foto lama hilang tanpa
        // pernah ada path yang tersimpan di database.
        $target = $this->authUser($id_user);
        if (! $target) {
            return $this->response->setJSON(['status' => false, 'message' => 'User tidak ditemukan — foto tidak disimpan.']);
        }

        $targetDir = public_path().'/'.'uploads/profiles/';
        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true)) {
            Log::error('[Useradmin::uploadFile] folder uploads/profiles gagal dibuat');

            return $this->response->setJSON(['status' => false, 'message' => 'Foto gagal disimpan di server — coba lagi atau lapor ke pengembang.']);
        }

        $newName = 'foto_'.$id_user.'.'.$ext;
        if (! $file->move($targetDir, $newName, true)) {
            Log::error('[Useradmin::uploadFile] move gagal untuk user#'.$id_user.': '.$file->getError());

            return $this->response->setJSON([
                'status' => false,
                'message' => 'Foto gagal disimpan di server — coba lagi, dan bila berulang lapor ke pengembang.',
            ]);
        }
        $src = 'uploads/profiles/'.$newName;

        $db = DB::connection();
        try {
            $db->table('users')->where('id', $id_user)->update(['foto' => $src]);
        } catch (\Throwable $e) {
            Log::error('[Useradmin::uploadFile] update kolom foto users#'.$id_user.' gagal: '.$e->getMessage());

            return $this->response->setJSON([
                'status' => false,
                'message' => 'File foto sudah tersimpan, tapi data user gagal diperbarui — ulangi unggah foto.',
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'src' => url($src).'?v='.time(),
            'filename' => $newName,
            'message' => 'Foto profil berhasil diperbarui.',
        ]);
    }

    public function deleteFile()
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        $idUser = (int) $this->request->input('id_user');
        if (! $this->isAdmin() && $idUser !== (int) $this->getUserId()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Akses ditolak']);
        }
        if ($idUser <= 0 || ! $this->authUser($idUser)) {
            return $this->response->setJSON(['status' => false, 'message' => 'ID User tidak valid.']);
        }

        $src = (string) $this->request->input('src');
        $rel = $this->resolveProfilePath($src);
        if ($rel !== null && @unlink(public_path().'/'.$rel) === false && is_file(public_path().'/'.$rel)) {
            // File masih ada di disk: jangan laporkan penghapusan yang tidak terjadi.
            Log::error('[Useradmin::deleteFile] unlink gagal untuk '.$rel);

            return $this->response->setJSON([
                'status' => false,
                'message' => 'File foto tidak bisa dihapus dari server — hapus kolomnya saja bila perlu.',
            ]);
        }

        $db = DB::connection();
        try {
            $db->table('users')->where('id', $idUser)->update(['foto' => null]);
        } catch (\Throwable $e) {
            Log::error('[Useradmin::deleteFile] update kolom foto users#'.$idUser.' gagal: '.$e->getMessage());

            return $this->response->setJSON(['status' => false, 'message' => 'Data gagal diperbarui — periksa log aplikasi.']);
        }

        return $this->response->setJSON(['status' => true, 'message' => 'Foto profil berhasil dihapus.']);
    }

    /**
     * Normalisasi src foto profil: wajib file nyata di dalam uploads/profiles/.
     * Tanpa batas ini, src='../../.env' diikat ke public_path() . '/' sehingga pengguna ter-autentikasi
     * bisa menghapus file di luar webroot (pola yang sama sudah dipakai Dataguru::
     * resolveDeletablePath dan Settings::resolveMediaPath).
     */
    private function resolveProfilePath(?string $src): ?string
    {
        $rel = ltrim(str_replace(url('/'), '', (string) $src), '/');
        $rel = str_replace('\\', '/', $rel);
        if ($rel === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $rel)) {
            return null;
        }
        if (strpos($rel, '..') !== false) {
            return null;
        }
        if (strpos($rel, 'uploads/profiles/') !== 0) {
            return null;
        }
        $abs = realpath(public_path().'/'.$rel);
        $root = realpath(public_path().'/'.'uploads/profiles');
        if ($abs === false || $root === false || strpos($abs, $root) !== 0 || ! is_file($abs)) {
            return null;
        }
        // public_path() . '/' bisa berakhir '/' (Linux) atau '\' (Windows): potong dari akar
        // yang sudah dibersihkan, lalu paksa separator maju agar sama dengan
        // nilai yang tersimpan di kolom foto.
        $rel = substr($abs, strlen(rtrim(public_path().'/', '/\\')) + 1);
        $rel = str_replace('\\', '/', $rel);

        // Jaga irisan: hasil harus tetap berada di bawah uploads/profiles/,
        // kalau tidak (mis. salah potong) lebih baik tolak daripada unlink
        // file yang salah.
        return strpos($rel, 'uploads/profiles/') === 0 ? $rel : null;
    }

    public function change_password()
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }
        // Username selalu divalidasi; password hanya wajib bila ingin diganti
        // (kolom password dikosongkan = hanya ganti username)
        $wantPassword = trim((string) $this->request->input('new')) !== ''
            || trim((string) $this->request->input('new_confirm')) !== ''
            || trim((string) $this->request->input('old')) !== '';
        $rules = [
            'username' => 'required|string|min:4|max:30|regex:/^[A-Za-z0-9_.-]+$/',
        ];
        if ($wantPassword) {
            $rules['old'] = 'required';
            $rules['new'] = 'required|string|min:6|max:72';
            $rules['new_confirm'] = 'required|same:new';
        }

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status' => false,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $id = (int) $this->request->input('id');
        $old = $this->request->input('old');
        $new = $this->request->input('new');
        $newUsername = trim((string) $this->request->input('username'));

        // Non-admin hanya boleh mengganti password akunnya sendiri
        if (! $this->isAdmin() && $id !== (int) $this->getUserId()) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Akses ditolak']);
        }

        $user = $this->authUser($id);
        if (! $user) {
            return $this->response->setJSON(['status' => false, 'msg' => 'User not found']);
        }

        // Verifikasi password lama dilakukan SEBELUM ada tulis-menulis.
        // Urutan lama (ganti password, lalu validasi rename) meninggalkan akun
        // dengan password baru ketika rename ditolak.
        if ($wantPassword && ! Hash::check((string) $old, (string) $user->password)) {
            return $this->response->setJSON(['status' => false, 'msg' => 'Password lama salah.']);
        }

        $msg = $wantPassword ? 'Password berhasil diubah' : 'Username berhasil diperbarui';
        $renamed = false;
        // Ganti username bila diubah (form menyediakan field username)
        if ($newUsername !== '' && strcasecmp($newUsername, (string) $user->username) !== 0) {
            // users + baris master tersambung dalam satu transaksi: join
            // roster memakai username, rename separuh jalan membuat guru/siswa
            // kehilangan akunnya dan "aktifkan" lalu membuat akun kedua.
            $hasilRename = $this->authRenameUser((int) $user->id, $newUsername);
            if (! $hasilRename['status']) {
                return $this->response->setJSON(['status' => false, 'msg' => $hasilRename['message']]);
            }
            $renamed = (bool) ($hasilRename['changed'] ?? false);
            if ($renamed) {
                if ((int) auth()->id() === (int) $user->id) {
                    session()->put('username', $newUsername);
                }
                $msg = $wantPassword ? 'Password & username berhasil diubah' : 'Username berhasil diubah';
            }
        }

        $usernameLogin = $renamed ? $newUsername : (string) $user->username;
        if ($wantPassword && ! $this->authChangePassword($usernameLogin, (string) $old, (string) $new)) {
            return $this->response->setJSON([
                'status' => false,
                'msg' => $renamed
                    ? 'Username sudah berganti, tapi password gagal diubah — ulangi ubah password.'
                    : 'Gagal mengubah password (password lama salah?)',
            ]);
        }

        return $this->response->setJSON(['status' => true, 'msg' => $msg]);
    }
}

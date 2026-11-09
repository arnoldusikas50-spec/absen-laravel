<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AuthModel;
use App\Models\User;
use App\Models\LogModel;
use App\Models\SettingsModel;

/**
 * Auth Controller - Migrated from CI3 Auth controller
 * Handles user authentication (login, logout, registration)
 */
class AuthController extends BaseController
{
    protected LogModel $logModel;
    protected SettingsModel $settingsModel;

    public function __construct()
    {
        parent::__construct();
        $this->authModel = new AuthModel();
        $this->logModel = new LogModel();
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Halaman login utama — semua role (Admin, Guru, Siswa)
     * Route: GET /auth
     */
    public function index()
    {
        // Jika sudah login, arahkan sesuai role (seperti GuestFilter CI4:
        // siswa -> siswa/presensi, guru -> presensi, lainnya -> dashboard).
        if ($this->session->get('is_login')) {
            $this->loginGuard((int) $this->session->get('user_id'));
            return redirect()->to($this->determineRedirect(
                (object) [
                    'role_name' => $this->session->get('role_name'),
                    'company' => $this->session->get('school_uuid'),
                ],
                false
            ));
        }

        $data['setting'] = $this->getSettingRow();
        return view('backend.login', $data);
    }

    /**
     * Halaman login siswa — redirect ke halaman login utama
     * Route: GET /auth/siswa
     */
    public function siswa()
    {
        return redirect()->to('auth');
    }

    /**
     * Render login page based on variant
     */
    private function renderLoginPage(string $variant = 'staff')
    {

        // Check if setup is complete
        if (!function_exists('database_connected') || !database_connected() || is_database_empty() || is_setting_empty()) {
            return redirect()->to('install');
        }

        // Already logged in - redirect
        if ($this->session->get('is_login')) {
            $user = $this->findUserById((int) $this->session->get('user_id'));
            $redirectUrl = $this->determineRedirect($user, false);
            return redirect()->to($redirectUrl);
        }

        $setting = $this->getSettingRow();

        $data = [
            'setting' => $setting,
            'identity' => [
                'name' => 'username',
                'id' => 'identity',
                'type' => 'text',
                'placeholder' => 'Username',
                'autofocus' => 'autofocus',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ],
            'username' => [
                'name' => 'username',
                'id' => 'identity',
                'type' => 'text',
                'placeholder' => 'Username',
                'autofocus' => 'autofocus',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ],
            'password' => [
                'name' => 'password',
                'id' => 'password',
                'type' => 'password',
                'placeholder' => 'Password',
                'class' => 'form-control',
            ],
            'message' => session('message') ?? '',
            'login_variant' => $variant,
            'show_mode_selector' => false,
            'cbt_only_default' => '0',
            'login_title' => 'PRESENSI & JURNAL',
        ];

        // Use appropriate view based on variant
        if ($variant !== 'siswa') {
            return view('auth.login', $data);
        }

        return view('auth.index', $data);
    }

    /**
     * Handle login POST request
     */
    public function login()
    {

        try {
            $success = false;
            $message = '';
            $role = '0';
            $dataUser = null;
            $errors = [];

            $cbtOnly      = $this->request->input('cbt-only') === '1';
            $redirectUrl  = url('dashboard');

            if (strtolower($this->request->method()) === 'post') {
                // Validation — tidak perlu login_variant lagi
                $rules = [
                    'username' => 'required|min_length[3]|max_length[50]',
                    'password' => 'required|min_length[4]|max_length[50]',
                ];

                if (!$this->validate($rules)) {
                    $validation = $this->validation();
                    $message = 'Username atau Password salah!';
                    $errors  = [
                        'username' => $validation->getError('username'),
                        'password' => $validation->getError('password'),
                    ];
                    return $this->respond(false, $message, null, $errors);
                }

                $username = (string) $this->request->input('username');
                $password = (string) $this->request->input('password');

                // Check login attempts — hanya dalam jendela 5 menit terakhir
                $detikKunci = 300;
                $percobaan = $this->cekErrorLogin($username, $detikKunci);
                if (count($percobaan) >= 3) {
                    $message = 'Anda sudah 3x melakukan percobaan login. Silakan tunggu 5 menit atau hubungi Administrator.';
                    return $this->respond(false, $message);
                }

                // Get user
                $user = $this->findUserForLogin($username);

                if ($user) {
                    $roleName = strtolower($user->role_name ?? '');

                    // Cek apakah siswa aktif (hanya untuk role siswa)
                    $dataUser = $this->findSiswaAktif($username);
                    $isSiswa  = $dataUser !== null;

                    if ($isSiswa && isset($dataUser->status) && $dataUser->status !== '1') {
                        return $this->respond(false, 'Akun Anda dinonaktifkan!');
                    }

                    if ($user->active === '1') {
                        // Verify password
                        if (password_verify($password, $user->password)) {
                            $role = $user->role;

                            // Set session data
                            $idGuru = 0;
                            try {
                                $dbConn = \Illuminate\Support\Facades\DB::connection();
                                if (\Illuminate\Support\Facades\Schema::hasTable('master_guru')) {
                                    $guruRow = $dbConn->table('master_guru')->where('id_user', $user->id)->first();
                                    if ($guruRow && !empty($guruRow->id_guru)) {
                                        $idGuru = (int) $guruRow->id_guru;
                                    }
                                }
                            } catch (\Throwable $e) {}

                            // Cegah session fixation: pakai ID sesi baru
                            // sebelum data login ditulis.
                            $this->session->regenerate(true);

                            $this->session->set([
                                'user_id'     => $user->id,
                                'id_guru'     => $idGuru,
                                'username'    => $user->username,
                                'nama'        => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
                                'email'       => $user->email,
                                'role'        => $user->group_id,
                                'role_name'   => $user->role_name,
                                'foto'        => $user->foto,
                                'school_uuid' => $user->company ?? null,
                                'is_login'    => true,
                                'login_variant' => $roleName === 'siswa' ? 'siswa' : 'staff',
                            ]);

                            // Samakan dengan guard 'web' Laravel agar middleware
                            // bawaan 'auth' dan RoleMiddleware mengenalinya.
                            $this->loginGuard((int) $user->id);

                            $this->resetErrorLogin($username);
                            $message = 'Login berhasil';
                            $success = true;
                            $redirectUrl = $this->determineRedirect($user, $cbtOnly);

                            // PWA "tetap login" 30 hari — hanya guru & siswa
                            // (admin/panitia dikecualikan demi keamanan)
                            if (in_array($roleName, ['guru', 'siswa'], true)) {
                                \App\Libraries\RememberLogin::issue((int) $user->id);
                            }

                            // Log with explicit userId since session might not be committed yet
                            $this->saveAuditLog(1, 'Login', (int) $user->id);
                        } else {
                            // Wrong password
                            $this->recordErrorLogin([
                                'ip_address' => $this->request->ip(),
                                'login' => $username,
                                'time' => time(),
                            ]);
                            $message = 'Username atau Password salah!';
                        }
                    } else {
                        $message = 'Akun Anda dinonaktifkan!';
                    }
                } else {
                    $message = 'Akun tidak terdaftar!';
                }
            }

            $respData = [
                'role' => $role,
                'user' => $dataUser,
                'url' => 'dashboard',
                'redirect' => $redirectUrl
            ];

            return $this->respond($success, $message, $respData, $errors);

        } catch (\Throwable $th) {
            Log::error('[Auth::login] ' . $th->getMessage() . ' di ' . $th->getFile() . ':' . $th->getLine());
            return $this->respond(false, 'Terjadi kesalahan pada server. Silakan coba lagi.');
        }
    }

    /**
     * Determine redirect URL based on user role
     */
    private function determineRedirect(?object $user, bool $cbtOnly): string
    {
        $default = url('dashboard');

        if (!$user) {
            return $default;
        }

        // Admin Sekolah redirect
        if (!empty($user->company)) {
            return url('sekolahpanel');
        }

        $roleName = strtolower($user->role_name ?? '');

        if ($cbtOnly && $roleName === 'siswa') {
            return url('dashboard');
        }

        if ($roleName === 'siswa') {
            return url('siswa/presensi');
        }

        // Guru → halaman presensi (absensi harian)
        if ($roleName === 'guru') {
            return url('presensi');
        }

        // Admin & Panitia → dashboard
        if (in_array($roleName, ['admin', 'panitia'])) {
            return url('dashboard');
        }

        return $default;
    }

    /**
     * Handle logout - redirect ke /auth
     */
    public function logout()
    {
        \App\Libraries\RememberLogin::clear();
        $this->saveAuditLog(2, 'Logout');
        $this->session->destroy();
        return redirect()->to('auth')->with('success', 'Anda telah berhasil keluar.');
    }

    // ------------------------------------------------------------------
    // AuthModel CI4 diimplementasikan di sini karena model Laravel
    // (App\Models\AuthModel) belum mem-port method-methodnya.
    // ------------------------------------------------------------------

    /**
     * CI4 AuthModel::login() — user + info role (users_groups + groups).
     */
    private function findUserForLogin(string $username): ?object
    {
        try {
            return DB::table('users as a')
                ->select('a.*', 'b.user_id', 'b.group_id', 'c.id as role', 'c.name as role_name', 'c.description')
                ->join('users_groups as b', 'a.id', '=', 'b.user_id')
                ->join('groups as c', 'b.group_id', '=', 'c.id')
                ->where('a.username', $username)
                ->first();
        } catch (\Throwable $e) {
            Log::error('[Auth::findUserForLogin] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * CI4 AuthModel::user() — baris users berdasarkan id.
     */
    private function findUserById(int $id): ?object
    {
        try {
            return DB::table('users')->where('id', $id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * CI4 AuthModel::isAktif() — status siswa (master_siswa + buku_induk).
     */
    private function findSiswaAktif(string $username): ?object
    {
        try {
            return DB::table('master_siswa as a')
                ->select('a.id_siswa', 'b.status')
                ->leftJoin('buku_induk as b', 'a.id_siswa', '=', 'b.id_siswa')
                ->where('a.username', $username)
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * CI4 AuthModel::cekErrorLogin() — percobaan login gagal dalam jendela detik.
     */
    private function cekErrorLogin(string $username, ?int $dalamDetik = null): array
    {
        try {
            if (!Schema::hasTable('login_attempts')) {
                return [];
            }
            $q = DB::table('login_attempts')->where('login', $username);
            if ($dalamDetik !== null && $dalamDetik > 0) {
                $q->where('time', '>=', time() - $dalamDetik);
            }
            return $q->orderBy('time', 'ASC')->get()->all();
        } catch (\Throwable $e) {
            Log::error('[Auth::cekErrorLogin] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * CI4 AuthModel::errorLogin() — catat percobaan login gagal.
     */
    private function recordErrorLogin(array $data): bool
    {
        try {
            if (!Schema::hasTable('login_attempts')) {
                return false;
            }
            return DB::table('login_attempts')->insert($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * CI4 AuthModel::resetErrorLogin() — hapus riwayat percobaan gagal.
     */
    private function resetErrorLogin(string $username): bool
    {
        try {
            if (!Schema::hasTable('login_attempts')) {
                return false;
            }
            return (bool) DB::table('login_attempts')->where('login', $username)->delete();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Masukkan user ke guard 'web' Laravel (dipakai middleware 'auth' dan
     * RoleMiddleware). Dipanggil setelah login sukses dan saat halaman /auth
     * dikunjungi dengan session CI4 (mis. setelah RememberLogin::restore(),
     * yang hanya mengisi session, bukan guard).
     */
    private function loginGuard(int $userId): void
    {
        if ($userId <= 0 || auth()->check()) {
            return;
        }
        try {
            $user = User::query()->where('id', $userId)->first();
            if ($user) {
                auth()->login($user);
            }
        } catch (\Throwable $e) {
            Log::error('[Auth::loginGuard] ' . $e->getMessage());
        }
    }
}

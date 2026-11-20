<?php

namespace App\Http\Controllers;

use App\Libraries\RememberLogin;
use App\Models\AuthModel;
use App\Models\LogModel;
use App\Models\SettingsModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Handles user authentication (login, logout, registration)
 */
class AuthController extends BaseController
{
    protected LogModel $logModel;

    protected SettingsModel $settingsModel;

    public function __construct()
    {
        parent::__construct();
        $this->authModel = new AuthModel;
        $this->logModel = new LogModel;
        $this->settingsModel = new SettingsModel;
    }

    /**
     * Halaman login utama — semua role (Admin, Guru, Siswa)
     * Route: GET /auth
     */
    public function index()
    {
        // siswa -> siswa/presensi, guru -> presensi, lainnya -> dashboard).
        if (auth()->check()) {
            $this->loginGuard((int) auth()->id());

            return redirect()->to($this->determineRedirect(
                (object) [
                    'role_name' => auth()->user()?->role_name,
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

            $cbtOnly = $this->request->input('cbt-only') === '1';
            $redirectUrl = url('dashboard');

            if (strtolower($this->request->method()) === 'post') {
                // Validation — tidak perlu login_variant lagi
                $rules = [
                    'username' => 'required|string|min:3|max:50',
                    'password' => 'required|string|min:4|max:50',
                ];

                if (! $this->validate($rules)) {
                    $validation = $this->validation();
                    $message = 'Username atau Password salah!';
                    $errors = [
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

                // Throttle tambahan per-IP: lockout di atas hanya mengunci satu
                // username, sehingga penyerang bisa menyemprot banyak username.
                // Hanya kegagalan yang dihitung agar login massal sah (mis. satu
                // IP NAT sekolah) tidak ikut terkunci.
                $ipKey = 'login_ip_'.md5((string) $this->request->ip());
                if (RateLimiter::tooManyAttempts($ipKey, 30)) {
                    $message = 'Terlalu banyak percobaan login dari jaringan ini. Silakan tunggu 5 menit.';

                    return $this->respond(false, $message);
                }

                // Get user
                $user = $this->findUserForLogin($username);

                if ($user) {
                    $roleName = strtolower($user->role_name ?? '');

                    // Cek apakah siswa aktif (hanya untuk role siswa)
                    $dataUser = $this->findSiswaAktif($username);
                    $isSiswa = $dataUser !== null;

                    // PDO mengembalikan kolom integer sebagai int, bukan string
                    if ($isSiswa && isset($dataUser->status) && (string) $dataUser->status !== '1') {
                        return $this->respond(false, 'Akun Anda dinonaktifkan!');
                    }

                    if ((string) $user->active === '1') {
                        // Verify password
                        if (Hash::check((string) $password, (string) $user->password)) {
                            $role = $user->role;

                            // Set session data
                            $idGuru = 0;
                            try {
                                $dbConn = DB::connection();
                                if (Schema::hasTable('master_guru')) {
                                    $guruRow = $dbConn->table('master_guru')->where('id_user', $user->id)->first();
                                    if ($guruRow && ! empty($guruRow->id_guru)) {
                                        $idGuru = (int) $guruRow->id_guru;
                                    }
                                }
                            } catch (\Throwable $e) {
                            }

                            // Cegah session fixation: pakai ID sesi baru
                            // sebelum data login ditulis.
                            $this->session->regenerate(true);

                            $this->session->set([
                                'user_id' => $user->id,
                                'id_guru' => $idGuru,
                                'username' => $user->username,
                                'nama' => $user->first_name.($user->last_name ? ' '.$user->last_name : ''),
                                'email' => $user->email,
                                'role' => $user->group_id,
                                'role_name' => $user->role_name,
                                'foto' => $user->foto,
                                'school_uuid' => $user->company ?? null,
                                'is_login' => true,
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
                            // (admin dikecualikan demi keamanan)
                            if (in_array($roleName, ['guru', 'siswa'], true)) {
                                RememberLogin::issue((int) $user->id);
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
                            RateLimiter::hit($ipKey, $detikKunci);
                            $message = 'Username atau Password salah!';
                        }
                    } else {
                        RateLimiter::hit($ipKey, $detikKunci);
                        $message = 'Akun Anda dinonaktifkan!';
                    }
                } else {
                    RateLimiter::hit($ipKey, $detikKunci);
                    $message = 'Akun tidak terdaftar!';
                }
            }

            $respData = [
                'role' => $role,
                'user' => $dataUser,
                'url' => 'dashboard',
                'redirect' => $redirectUrl,
            ];

            return $this->respond($success, $message, $respData, $errors);

        } catch (\Throwable $th) {
            Log::error('[Auth::login] '.$th->getMessage().' di '.$th->getFile().':'.$th->getLine());

            return $this->respond(false, 'Terjadi kesalahan pada server. Silakan coba lagi.');
        }
    }

    /**
     * Determine redirect URL based on user role
     */
    private function determineRedirect(?object $user, bool $cbtOnly): string
    {
        $default = url('dashboard');

        if (! $user) {
            return $default;
        }

        // Akun multi-sekolah (kolom `company`) dulu diarahkan ke '/sekolahpanel',
        // tetapi halaman itu tidak ada di build ini (404). Sementara ini mereka
        // memakai alur berbasis role di bawah sampai panelnya ditambahkan.

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

        // Admin → dashboard
        if ($roleName === 'admin') {
            return url('dashboard');
        }

        return $default;
    }

    /**
     * Handle logout - redirect ke /auth
     */
    public function logout()
    {
        RememberLogin::clear();
        $this->saveAuditLog(2, 'Logout');
        $this->session->destroy();

        return redirect()->to('auth')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Cari user aktif beserta nama grup untuk proses login.
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
            Log::error('[Auth::findUserForLogin] '.$e->getMessage());

            return null;
        }
    }

    private function findUserById(int $id): ?object
    {
        try {
            return DB::table('users')->where('id', $id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

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

    private function cekErrorLogin(string $username, ?int $dalamDetik = null): array
    {
        try {
            if (! Schema::hasTable('login_attempts')) {
                return [];
            }
            $q = DB::table('login_attempts')->where('login', $username);
            if ($dalamDetik !== null && $dalamDetik > 0) {
                $q->where('time', '>=', time() - $dalamDetik);
            }

            return $q->orderBy('time', 'ASC')->get()->all();
        } catch (\Throwable $e) {
            Log::error('[Auth::cekErrorLogin] '.$e->getMessage());

            return [];
        }
    }

    private function recordErrorLogin(array $data): bool
    {
        try {
            if (! Schema::hasTable('login_attempts')) {
                return false;
            }

            return DB::table('login_attempts')->insert($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resetErrorLogin(string $username): bool
    {
        try {
            if (! Schema::hasTable('login_attempts')) {
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
            Log::error('[Auth::loginGuard] '.$e->getMessage());
        }
    }
}

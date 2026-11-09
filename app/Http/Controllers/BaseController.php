<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * BaseController — port of CI4 App\Controllers\BaseController to Laravel 13.
 *
 * CI4 controllers accessed request/session/response through $this->request,
 * $this->session and $this->response. Those properties are kept here so the
 * ported controllers work unchanged:
 *   - $this->request  : Illuminate\Http\Request (current request)
 *   - $this->session  : CiSession adapter (CI4-style get/set/destroy/...)
 *   - $this->response : CiResponse adapter (setJSON/setStatusCode/...)
 *
 * Helpers ported 1:1 from CI4: isLoggedIn, getUserId, getUserRole, inGroup,
 * isAdmin/isGuru/isPanitia/isKepsek/isSiswa, requireLogin, requireRole,
 * respond, getUser, showError, renderView, maybe_unserialize, getTpSmt.
 *
 * Validation: $this->validate($rules) mirrors CI4 (bool + $this->validation()
 * for getError/getErrors); CI4 rule names are translated to Laravel rules.
 * Throttling: $this->throttleCheck($key, $capacity, $seconds) mirrors
 * CI4 Throttler::check().
 */
class BaseController extends Controller
{
    /** @var \Illuminate\Http\Request */
    protected $request;

    /** @var CiSession */
    protected $session;

    /** @var CiResponse */
    protected $response;

    /** @var \Illuminate\Database\Connection|null */
    protected $db;

    protected AuthModel $authModel;

    /** @var \Illuminate\Contracts\Validation\Validator|null */
    protected $validator;

    protected array $validationErrors = [];

    public function __construct()
    {
        // Kunci timezone aplikasi: seluruh logika presensi memakai Asia/Makassar.
        date_default_timezone_set('Asia/Makassar');

        $this->request = request();
        $this->session = new CiSession();
        $this->response = new CiResponse();

        try {
            $this->authModel = new AuthModel();
        } catch (\Throwable $e) {
            // Database connection failed - will be handled by controllers
        }
    }

    // ------------------------------------------------------------------
    // Auth / role helpers (ported from CI4 BaseController)
    // ------------------------------------------------------------------

    protected function isLoggedIn(): bool
    {
        return (bool) session()->get('is_login');
    }

    protected function getUserId(): ?int
    {
        $id = session()->get('user_id');
        return $id === null ? null : (int) $id;
    }

    protected function getUserRole(): ?string
    {
        return session()->get('role_name');
    }

    protected function inGroup(string|array $group): bool
    {
        $role = strtolower((string) (session()->get('role_name') ?? ''));
        if ($role === 'pengawas') {
            $role = 'panitia';
        }
        $norm = static function ($g): string {
            $g = strtolower((string) $g);
            return $g === 'pengawas' ? 'panitia' : $g;
        };
        if (is_array($group)) {
            $group = array_map($norm, $group);
            return in_array($role, $group, true);
        }
        return $role === $norm($group);
    }

    protected function isAdmin(): bool
    {
        $role = session()->get('role');
        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        return (string) $role === '1' || $roleName === 'admin' || $this->inGroup('admin');
    }

    protected function isGuru(): bool
    {
        $role = session()->get('role');
        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        return (string) $role === '2' || $roleName === 'guru' || $this->inGroup('guru');
    }

    protected function isPanitia(): bool
    {
        $role = session()->get('role');
        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        return (string) $role === '4'
            || in_array($roleName, ['panitia', 'pengawas'], true)
            || $this->inGroup(['panitia', 'pengawas']);
    }

    protected function isKepsek(): bool
    {
        return is_kepsek((int) (session()->get('user_id') ?? 0));
    }

    protected function isSiswa(): bool
    {
        $role = session()->get('role');
        $roleName = strtolower((string) (session()->get('role_name') ?? ''));
        return (string) $role === '3' || $roleName === 'siswa' || $this->inGroup('siswa');
    }

    protected function requireLogin()
    {
        if ($this->isLoggedIn()) {
            return null;
        }
        if ($this->request->ajax() || $this->request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Session expired. Please login again.',
            ], 401);
        }
        return redirect()->to('auth')->with('message', 'Silakan login terlebih dahulu.');
    }

    protected function requireRole(string|array $roles)
    {
        if ($deny = $this->requireLogin()) {
            return $deny;
        }
        $roles = (array) $roles;
        if ($this->inGroup($roles)) {
            return null;
        }
        $idMap = ['admin' => '1', 'guru' => '2', 'siswa' => '3', 'panitia' => '4', 'pengawas' => '4'];
        $roleId = (string) (session()->get('role') ?? '');
        foreach ($roles as $r) {
            $key = strtolower((string) $r);
            if ($key === 'pengawas') {
                $key = 'panitia';
            }
            if (isset($idMap[$key]) && $roleId === $idMap[$key]) {
                return null;
            }
        }
        if ($this->request->ajax() || $this->request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }
        return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
    }

    protected function respond(bool $status, string $message = '', $data = null, $errors = null, int $httpCode = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
        ], $httpCode);
    }

    protected function getAssetsPath(): string
    {
        return asset('') . 'assets/';
    }

    protected function getUser(): ?object
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return null;
        }

        try {
            $user = DB::table('users')->where('id', $userId)->first();
        } catch (\Throwable $e) {
            return null;
        }

        if ($user) {
            $user->role = session()->get('role');
            $user->role_name = session()->get('role_name');
        }

        return $user;
    }

    protected function showError(string $message, int $statusCode = 500)
    {
        return view('errors.html.error_' . $statusCode, [
            'message' => $message,
        ]);
    }

    protected function renderView(string $view, array $data = [])
    {
        $data['base_url'] = url('/');
        $data['user'] = $data['user'] ?? $this->getUser();

        return view($view, $data);
    }

    protected function maybe_unserialize($data)
    {
        if (empty($data)) {
            return [];
        }

        if (is_array($data) || is_object($data)) {
            return $data;
        }

        $decoded = json_decode($data);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $unserialized = @unserialize($data, ['allowed_classes' => false]);
        if ($unserialized !== false || $data === 'b:0;') {
            return $unserialized;
        }

        return $data;
    }

    protected function getTpSmt(): object
    {
        try {
            $tp = DB::table('master_tp')->where('active', 1)->first();
        } catch (\Throwable $e) {
            $tp = null;
        }
        try {
            $smt = DB::table('master_smt')->where('active', 1)->first();
        } catch (\Throwable $e) {
            $smt = null;
        }

        return (object) ['tp' => $tp, 'smt' => $smt];
    }

    // ------------------------------------------------------------------
    // Setting helpers (CI4 SettingsModel/DashboardModel::getSetting port)
    // ------------------------------------------------------------------

    /**
     * Baris pertama tabel `setting` + alias kompatibilitas CI3
     * (nama_sekolah, logo, website). Menggantikan ->getSetting() CI4.
     */
    protected function getSettingRow(): ?object
    {
        try {
            if (!Schema::hasTable('setting')) {
                return null;
            }
            $setting = DB::table('setting')->first();
        } catch (\Throwable $e) {
            return null;
        }

        if ($setting) {
            if (isset($setting->sekolah) && !isset($setting->nama_sekolah)) {
                $setting->nama_sekolah = $setting->sekolah;
            }
            if (isset($setting->logo_kiri) && !isset($setting->logo)) {
                $setting->logo = $setting->logo_kiri;
            }
            if (isset($setting->web) && !isset($setting->website)) {
                $setting->website = $setting->web;
            }
        }

        return $setting;
    }

    /**
     * Menggantikan SettingsModel::updateSetting() CI4: update baris pertama
     * tabel `setting`, atau insert bila belum ada.
     */
    protected function updateSettingRow(array $data): bool
    {
        try {
            if (!Schema::hasTable('setting')) {
                return false;
            }
            $setting = DB::table('setting')->first();
            if ($setting) {
                $idCol = isset($setting->id_setting) ? 'id_setting' : 'id';
                return (bool) DB::table('setting')->where($idCol, $setting->{$idCol})->update($data);
            }
            return DB::table('setting')->insert($data);
        } catch (\Throwable $e) {
            Log::error('[BaseController::updateSettingRow] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Menggantikan LogModel::saveLog() CI4 (tabel `logs`).
     */
    protected function saveAuditLog(int $aktivitas, string $keterangan = '', ?int $userId = null): bool
    {
        $userId = $userId ?? (int) (session()->get('user_id') ?? 0);
        if (!$userId) {
            return false;
        }

        try {
            if (!Schema::hasTable('logs')) {
                return false;
            }
            return DB::table('logs')->insert([
                'id_user' => $userId,
                'aktivitas' => $aktivitas,
                'keterangan' => $keterangan,
                'ip_address' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
                'waktu' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Tambah kolom ke tabel (CI4 Forge::addColumn). Dipakai lazy-migration
     * di Settings / PresensiAdmin / Attendance. Definisi kolom gaya CI4:
     * ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => x].
     */
    protected function addTableColumns(string $table, array $fields): void
    {
        try {
            if (!Schema::hasTable($table)) {
                return;
            }
            $existing = Schema::getColumnListing($table);
            $toAdd = array_diff_key($fields, array_flip($existing));
            if (empty($toAdd)) {
                return;
            }
            Schema::table($table, function (Blueprint $t) use ($toAdd) {
                foreach ($toAdd as $name => $def) {
                    $this->addCiColumn($t, $name, $def);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[BaseController::addTableColumns] ' . $e->getMessage());
        }
    }

    protected function addCiColumn(Blueprint $table, string $name, array $def): void
    {
        $type = strtoupper((string) ($def['type'] ?? 'VARCHAR'));
        $constraint = $def['constraint'] ?? null;
        $nullable = !empty($def['null']);
        $default = $def['default'] ?? null;
        $hasDefault = array_key_exists('default', $def);
        $unsigned = !empty($def['unsigned']);
        $autoIncrement = !empty($def['auto_increment']);

        switch ($type) {
            case 'VARCHAR':
                $col = $table->string($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'CHAR':
                $col = $table->char($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'TEXT':
                $col = $table->text($name);
                break;
            case 'MEDIUMTEXT':
                $col = $table->mediumText($name);
                break;
            case 'LONGTEXT':
                $col = $table->longText($name);
                break;
            case 'INT':
            case 'INTEGER':
                $col = $autoIncrement ? $table->increments($name) : $table->integer($name);
                break;
            case 'BIGINT':
                $col = $autoIncrement ? $table->bigIncrements($name) : $table->bigInteger($name);
                break;
            case 'SMALLINT':
                $col = $table->smallInteger($name);
                break;
            case 'TINYINT':
                $col = $table->tinyInteger($name);
                break;
            case 'FLOAT':
                $col = $table->float($name);
                break;
            case 'DOUBLE':
                $col = $table->double($name);
                break;
            case 'DECIMAL':
                $bits = is_string($constraint) ? explode(',', $constraint) : [10, 2];
                $col = $table->decimal($name, (int) ($bits[0] ?? 10), (int) ($bits[1] ?? 2));
                break;
            case 'BOOLEAN':
            case 'BOOL':
                $col = $table->boolean($name);
                break;
            case 'DATETIME':
                $col = $table->dateTime($name);
                break;
            case 'DATE':
                $col = $table->date($name);
                break;
            case 'TIME':
                $col = $table->time($name);
                break;
            case 'TIMESTAMP':
                $col = $table->timestamp($name);
                break;
            case 'ENUM':
                $col = $table->enum($name, is_array($constraint) ? $constraint : [$constraint]);
                break;
            default:
                $col = $table->string($name, 255);
                break;
        }

        if ($unsigned && method_exists($col, 'unsigned')) {
            $col->unsigned();
        }
        if ($nullable) {
            $col->nullable();
        }
        if ($hasDefault && $default !== null) {
            $col->default($default);
        }
    }

    // ------------------------------------------------------------------
    // Validation (CI4 $this->validate() + Services::validation())
    // ------------------------------------------------------------------

    /**
     * Mirip $this->validate() CI4: return bool; error dibaca lewat
     * $this->validation()->getError('field') / getErrors().
     */
    protected function validate(array $rules, array $messages = []): bool
    {
        $laravelRules = [];
        $attributes = [];
        foreach ($rules as $field => $spec) {
            if (is_array($spec)) {
                $ruleStr = $spec['rules'] ?? '';
                if (isset($spec['label'])) {
                    $attributes[$field] = $spec['label'];
                }
                if (isset($spec['errors']) && is_array($spec['errors'])) {
                    foreach ($spec['errors'] as $rule => $msg) {
                        $messages[$field . '.' . $rule] = $msg;
                    }
                }
            } else {
                $ruleStr = $spec;
            }
            $laravelRules[$field] = $this->translateRules((string) $ruleStr);
        }

        $this->validator = Validator::make($this->request->all(), $laravelRules, $messages, $attributes);
        $this->validationErrors = [];
        if ($this->validator->fails()) {
            foreach ($this->validator->errors()->messages() as $field => $msgs) {
                $this->validationErrors[$field] = $msgs[0] ?? '';
            }
            return false;
        }
        return true;
    }

    protected function validation(): CiValidation
    {
        return new CiValidation($this->validator, $this->validationErrors);
    }

    /**
     * Terjemah aturan validasi CI4 -> Laravel.
     * Aturan yang tidak dikenal diabaikan (dicatat di log).
     */
    protected function translateRules(string $ruleStr): string
    {
        $out = [];
        foreach (explode('|', $ruleStr) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^(\w+)\[(.*)\]$/', $part, $m)) {
                $name = $m[1];
                $arg = $m[2];
            } else {
                $name = $part;
                $arg = null;
            }
            switch ($name) {
                case 'required':
                    $out[] = 'required';
                    break;
                case 'permit_empty':
                    $out[] = 'nullable';
                    break;
                case 'if_exist':
                    break;
                case 'min_length':
                    $out[] = 'min:' . $arg;
                    break;
                case 'max_length':
                    $out[] = 'max:' . $arg;
                    break;
                case 'exact_length':
                    $out[] = 'size:' . $arg;
                    break;
                case 'numeric':
                case 'decimal':
                    $out[] = 'numeric';
                    break;
                case 'integer':
                    $out[] = 'integer';
                    break;
                case 'valid_email':
                    $out[] = 'email';
                    break;
                case 'valid_url':
                    $out[] = 'url';
                    break;
                case 'valid_ip':
                    $out[] = 'ip';
                    break;
                case 'valid_date':
                    $out[] = 'date';
                    break;
                case 'alpha':
                    $out[] = 'alpha';
                    break;
                case 'alpha_numeric':
                    $out[] = 'alpha_num';
                    break;
                case 'alpha_dash':
                    $out[] = 'alpha_dash';
                    break;
                case 'in_list':
                    $out[] = 'in:' . $arg;
                    break;
                case 'matches':
                    $out[] = 'same:' . $arg;
                    break;
                case 'differs':
                    $out[] = 'different:' . $arg;
                    break;
                case 'is_unique':
                    $bits = explode(',', (string) $arg);
                    $tf = explode('.', $bits[0]);
                    $r = 'unique:' . $tf[0] . ',' . ($tf[1] ?? 'id');
                    if (isset($bits[2])) {
                        $r .= ',' . $bits[2] . ',' . $bits[1];
                    }
                    $out[] = $r;
                    break;
                case 'is_not_unique':
                    $out[] = 'exists:' . str_replace('.', ',', (string) $arg);
                    break;
                case 'greater_than':
                    $out[] = 'gt:' . $arg;
                    break;
                case 'greater_than_equal_to':
                    $out[] = 'gte:' . $arg;
                    break;
                case 'less_than':
                    $out[] = 'lt:' . $arg;
                    break;
                case 'less_than_equal_to':
                    $out[] = 'lte:' . $arg;
                    break;
                case 'required_with':
                    $out[] = 'required_with:' . $arg;
                    break;
                case 'required_without':
                    $out[] = 'required_without:' . $arg;
                    break;
                case 'uploaded':
                    $out[] = 'required';
                    $out[] = 'file';
                    break;
                case 'max_size':
                    $bits = explode(',', (string) $arg);
                    $out[] = 'max:' . ($bits[1] ?? 2048);
                    break;
                case 'mime_in':
                    $bits = explode(',', (string) $arg, 2);
                    $out[] = 'mimetypes:' . ($bits[1] ?? '');
                    break;
                case 'ext_in':
                    $bits = explode(',', (string) $arg);
                    array_shift($bits);
                    $out[] = 'mimes:' . implode(',', $bits);
                    break;
                case 'is_image':
                    $out[] = 'image';
                    break;
                case 'regex_match':
                    $out[] = 'regex:' . $arg;
                    break;
                case 'string':
                    $out[] = 'string';
                    break;
                case 'array':
                    $out[] = 'array';
                    break;
                case 'boolean':
                    $out[] = 'boolean';
                    break;
                default:
                    Log::debug('[BaseController::translateRules] aturan CI4 diabaikan: ' . $name);
                    break;
            }
        }
        return implode('|', array_filter($out));
    }

    /**
     * Mirip CI4 Throttler::check($key, $capacity, $seconds): true bila
     * masih dalam batas, false bila sudah melebihi batas.
     */
    protected function throttleCheck(string $key, int $capacity, int $seconds): bool
    {
        if (RateLimiter::tooManyAttempts($key, $capacity)) {
            return false;
        }
        RateLimiter::hit($key, $seconds);
        return true;
    }

    // ------------------------------------------------------------------
    // AuthModel CI4 (dipakai Useradmin/Userguru/Userpanitia/Usersiswa
    // lewat $this->auth). Model Laravel belum mem-port method-methodnya,
    // jadi diimplementasikan di sini dengan DB query builder.
    // ------------------------------------------------------------------

    /** Kolom yang boleh ditulis ke tabel `users` (CI4 AuthModel::$allowedFields). */
    protected function usersAllowedFields(): array
    {
        return [
            'ip_address', 'username', 'password', 'email', 'activation_code',
            'forgotten_password_code', 'forgotten_password_time', 'remember_code',
            'created_on', 'last_login', 'active', 'first_name', 'last_name',
            'company', 'phone', 'foto', 'nomor_peserta',
        ];
    }

    /** CI4 AuthModel::user() — baris users + nama_lengkap. */
    protected function authUser(?int $id = null): ?object
    {
        $userId = $id ?? (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }
        try {
            $user = DB::table('users')->where('id', $userId)->first();
        } catch (\Throwable $e) {
            return null;
        }
        if ($user) {
            $user->nama_lengkap = ($user->first_name === $user->last_name)
                ? $user->first_name
                : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }
        return $user;
    }

    /** CI4 AuthModel::isExist() — user berdasarkan username. */
    protected function authIsExist(string $username): ?object
    {
        try {
            return DB::table('users')->where('username', $username)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * CI4 AuthModel::register() — buat akun login baru + baris users_groups.
     * Hanya membuat; bila username sudah dipakai, kembalikan 0 tanpa menulis.
     */
    protected function authRegister(array $data, string $group): int
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '') {
            Log::error('[AuthModel::register] username kosong — akun tidak dibuat');
            return 0;
        }
        $data['username'] = $username;

        if ($this->authIsExist($username)) {
            Log::error('[AuthModel::register] username "' . $username . '" sudah dipakai — tidak ada yang diubah');
            return 0;
        }

        $data['ip_address'] = $this->request->ip();
        $data['created_on'] = time();
        $data['active'] = '1';
        unset($data['id']);
        $data = array_intersect_key($data, array_flip($this->usersAllowedFields()));

        DB::beginTransaction();
        try {
            $id = (int) DB::table('users')->insertGetId($data);
            if ($id <= 0) {
                DB::rollBack();
                Log::error('[AuthModel::register] insert users gagal');
                return 0;
            }
            DB::table('users_groups')->insert([
                'user_id' => $id,
                'group_id' => (int) $group,
            ]);
            DB::commit();
            return $id;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AuthModel::register] ' . $e->getMessage());
            return 0;
        }
    }

    /** CI4 AuthModel::deleteUser() — hapus akun + grup + lepas master_guru. */
    protected function authDeleteUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        DB::beginTransaction();
        try {
            $ada = DB::table('users')->where('id', $id)->first();
            if (!$ada) {
                DB::rollBack();
                return false;
            }
            if (Schema::hasTable('master_guru') && Schema::hasColumn('master_guru', 'id_user')) {
                DB::table('master_guru')->where('id_user', $id)->update(['id_user' => null]);
            }
            DB::table('users_groups')->where('user_id', $id)->delete();
            DB::table('users')->where('id', $id)->delete();
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AuthModel::deleteUser#' . $id . '] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * CI4 AuthModel::renameUser() — ganti username akun + sinkron master.
     */
    protected function authRenameUser(int $userId, string $newUsername): array
    {
        $new = trim($newUsername);
        if ($userId <= 0) {
            return ['status' => false, 'message' => 'Akun tidak valid.'];
        }
        if (strlen($new) < 4 || strlen($new) > 30 || !preg_match('/^[A-Za-z0-9_.-]+$/', $new)) {
            return ['status' => false, 'message' => 'Username baru tidak valid (4–30 karakter: huruf/angka/titik/strip/underscore).'];
        }

        DB::beginTransaction();
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Akun tidak ditemukan.'];
            }
            $old = trim((string) $user->username);
            if ($old === $new) {
                DB::rollBack();
                return ['status' => true, 'message' => 'Username tidak berubah.', 'changed' => false];
            }
            $dupe = DB::table('users')->where('username', $new)->where('id', '!=', $userId)->count();
            if ($dupe > 0) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Username sudah dipakai akun lain.'];
            }

            DB::table('users')->where('id', $userId)->update(['username' => $new]);

            $ikut = 0;
            if (Schema::hasTable('master_guru') && Schema::hasColumn('master_guru', 'username')) {
                $ikut += DB::table('master_guru')
                    ->where(function ($q) use ($userId, $old) {
                        $q->where('id_user', $userId)->orWhere('username', $old);
                    })
                    ->update(['username' => $new]);
            }
            if (Schema::hasTable('master_siswa') && Schema::hasColumn('master_siswa', 'username')) {
                $ikut += DB::table('master_siswa')->where('username', $old)->update(['username' => $new]);
            }

            DB::commit();
            return [
                'status' => true,
                'message' => 'Username "' . $old . '" → "' . $new . '"' . ($ikut > 0 ? ' (+' . $ikut . ' baris master)' : ''),
                'changed' => true,
                'old' => $old,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AuthModel::renameUser#' . $userId . '] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal mengganti username — tidak ada yang berubah.'];
        }
    }

    /** CI4 AuthModel::groups() — semua baris users_groups. */
    protected function authGroups(): array
    {
        try {
            return DB::table('users_groups')->get()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** CI4 AuthModel::changePassword() / change_password(). */
    protected function authChangePassword(string $username, string $oldPassword, string $newPassword): bool
    {
        try {
            $user = DB::table('users')->where('username', $username)->first();
            if ($user && password_verify($oldPassword, $user->password)) {
                return (bool) DB::table('users')->where('username', $username)
                    ->update(['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
            }
        } catch (\Throwable $e) {
            Log::error('[AuthModel::changePassword] ' . $e->getMessage());
        }
        return false;
    }

    /** CI4 AuthModel::resetErrorLogin() — hapus riwayat percobaan gagal. */
    protected function authResetErrorLogin(string $username): bool
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
     * CI4 MasterModel::updateData($table, $data, $pk, $id).
     */
    protected function updateMasterData(string $table, array $data, string $pk, $id)
    {
        try {
            return DB::table($table)->where($pk, $id)->update($data);
        } catch (\Throwable $e) {
            Log::error('[BaseController::updateMasterData] ' . $e->getMessage());
            return false;
        }
    }
}

// ======================================================================
// CI4 compatibility adapters (defined in this file so no other file in
// app/ is touched).
// ======================================================================

/**
 * Adapter session gaya CI4 di atas session Laravel.
 * get/set/has/remove/destroy/regenerate/setFlashdata/getFlashdata.
 */
class CiSession
{
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return session()->all();
        }
        return session()->get($key, $default);
    }

    public function set($key, $value = null): void
    {
        if (is_array($key)) {
            session()->put($key);
            return;
        }
        session()->put([$key => $value]);
    }

    public function has($key): bool
    {
        return session()->has($key);
    }

    public function remove($key): void
    {
        session()->forget($key);
    }

    public function destroy(): void
    {
        try {
            auth()->logout();
        } catch (\Throwable $e) {
        }
        session()->invalidate();
    }

    public function regenerate(bool $destroy = false): void
    {
        session()->regenerate();
    }

    public function setFlashdata($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                session()->flash($k, $v);
            }
            return;
        }
        session()->flash($key, $value);
    }

    public function getFlashdata($key = null)
    {
        if ($key === null) {
            return session()->all();
        }
        return session()->get($key);
    }

    public function push($key, $value): void
    {
        session()->push($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value): void
    {
        $this->set($key, $value);
    }
}

/**
 * Adapter response gaya CI4 di atas response Laravel.
 * setStatusCode/setContentType/setHeader/setJSON/setBody/download/noCache.
 */
class CiResponse
{
    protected int $status = 200;
    protected array $headers = [];

    public function setStatusCode(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    public function setContentType(string $type): static
    {
        $this->headers['Content-Type'] = $type;
        return $this;
    }

    public function setHeader(string $name, $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function noCache(): static
    {
        $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate';
        return $this;
    }

    public function setJSON($data)
    {
        return response()->json($data, $this->status, $this->headers);
    }

    public function setBody($body)
    {
        return response($body, $this->status, $this->headers);
    }

    public function download(string $path, $name = null)
    {
        return response()->download($path, $name);
    }

    public function redirect(string $uri)
    {
        return redirect()->to($uri);
    }
}

/**
 * Adapter validation gaya CI4 (Services::validation()).
 */
class CiValidation
{
    public function __construct(
        protected $validator = null,
        protected array $errors = [],
    ) {}

    public function getError(string $field): string
    {
        if (isset($this->errors[$field])) {
            return (string) $this->errors[$field];
        }
        if ($this->validator) {
            return (string) $this->validator->errors()->first($field);
        }
        return '';
    }

    public function getErrors(): array
    {
        if (!empty($this->errors)) {
            return $this->errors;
        }
        if ($this->validator) {
            $out = [];
            foreach ($this->validator->errors()->messages() as $field => $msgs) {
                $out[$field] = $msgs[0] ?? '';
            }
            return $out;
        }
        return [];
    }

    public function hasError(string $field): bool
    {
        return $this->getError($field) !== '';
    }

    public function listErrors(): string
    {
        return implode("\n", $this->getErrors());
    }
}

/**
 * Adapter Forge gaya CI4 di atas Schema Laravel.
 * addColumn/addField/addPrimaryKey/addKey/addUniqueKey/createTable/
 * modifyColumn/dropTable/dropColumn.
 */
class CiForge
{
    protected array $fields = [];
    protected $primaryKey = null;
    protected array $keys = [];
    protected array $uniqueKeys = [];

    public function addField(array $fields): static
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function addPrimaryKey($key): static
    {
        $this->primaryKey = $key;
        return $this;
    }

    public function addKey($key, bool $primary = false): static
    {
        if ($primary) {
            $this->primaryKey = $key;
        } else {
            $this->keys[] = $key;
        }
        return $this;
    }

    public function addUniqueKey($key, ?string $name = null): static
    {
        $this->uniqueKeys[] = [$key, $name];
        return $this;
    }

    public function createTable(string $table, bool $ifNotExists = false): bool
    {
        try {
            if ($ifNotExists && Schema::hasTable($table)) {
                $this->reset();
                return true;
            }
            $fields = $this->fields;
            $primaryKey = $this->primaryKey;
            $keys = $this->keys;
            $uniqueKeys = $this->uniqueKeys;
            Schema::create($table, function (Blueprint $t) use ($fields, $primaryKey, $keys, $uniqueKeys) {
                $helper = new BaseControllerHelper();
                foreach ($fields as $name => $def) {
                    if (is_string($def)) {
                        $t->string($name, 255);
                        continue;
                    }
                    $helper->addCiColumn($t, $name, (array) $def);
                }
                if ($primaryKey !== null) {
                    if (is_array($primaryKey)) {
                        $t->primary($primaryKey);
                    } else {
                        $t->primary($primaryKey);
                    }
                }
                foreach ($keys as $key) {
                    $t->index(is_array($key) ? $key : [$key]);
                }
                foreach ($uniqueKeys as [$key, $name]) {
                    $t->unique(is_array($key) ? $key : [$key], $name);
                }
            });
            $this->reset();
            return true;
        } catch (\Throwable $e) {
            Log::error('[CiForge::createTable] ' . $e->getMessage());
            $this->reset();
            return false;
        }
    }

    public function addColumn(string $table, array $fields): bool
    {
        try {
            if (!Schema::hasTable($table)) {
                return false;
            }
            $existing = Schema::getColumnListing($table);
            $toAdd = array_diff_key($fields, array_flip($existing));
            if (empty($toAdd)) {
                return true;
            }
            $helper = new BaseControllerHelper();
            Schema::table($table, function (Blueprint $t) use ($toAdd, $helper) {
                foreach ($toAdd as $name => $def) {
                    $helper->addCiColumn($t, $name, (array) $def);
                }
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('[CiForge::addColumn] ' . $e->getMessage());
            return false;
        }
    }

    public function modifyColumn(string $table, array $fields): bool
    {
        try {
            if (!Schema::hasTable($table)) {
                return false;
            }
            $helper = new BaseControllerHelper();
            Schema::table($table, function (Blueprint $t) use ($fields, $helper) {
                foreach ($fields as $name => $def) {
                    $helper->addCiColumn($t, $name, array_merge((array) $def, ['_change' => true]));
                }
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('[CiForge::modifyColumn] ' . $e->getMessage());
            return false;
        }
    }

    public function dropTable(string $table, bool $ifExists = false): bool
    {
        try {
            if ($ifExists) {
                Schema::dropIfExists($table);
            } else {
                Schema::drop($table);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('[CiForge::dropTable] ' . $e->getMessage());
            return false;
        }
    }

    public function dropColumn(string $table, $column): bool
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->dropColumn($column);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('[CiForge::dropColumn] ' . $e->getMessage());
            return false;
        }
    }

    protected function reset(): void
    {
        $this->fields = [];
        $this->primaryKey = null;
        $this->keys = [];
        $this->uniqueKeys = [];
    }
}

/**
 * Helper kecil agar CiForge bisa memakai pemetaan kolom CI4 yang sama
 * dengan BaseController::addCiColumn tanpa mewarisi controller.
 */
class BaseControllerHelper
{
    public function addCiColumn(Blueprint $table, string $name, array $def): void
    {
        $change = !empty($def['_change']);
        unset($def['_change']);
        $type = strtoupper((string) ($def['type'] ?? 'VARCHAR'));
        $constraint = $def['constraint'] ?? null;
        $nullable = !empty($def['null']);
        $default = $def['default'] ?? null;
        $hasDefault = array_key_exists('default', $def);
        $unsigned = !empty($def['unsigned']);
        $autoIncrement = !empty($def['auto_increment']);

        switch ($type) {
            case 'VARCHAR':
                $col = $table->string($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'CHAR':
                $col = $table->char($name, is_numeric($constraint) ? (int) $constraint : 255);
                break;
            case 'TEXT':
                $col = $table->text($name);
                break;
            case 'MEDIUMTEXT':
                $col = $table->mediumText($name);
                break;
            case 'LONGTEXT':
                $col = $table->longText($name);
                break;
            case 'INT':
            case 'INTEGER':
                $col = $autoIncrement ? $table->increments($name) : $table->integer($name);
                break;
            case 'BIGINT':
                $col = $autoIncrement ? $table->bigIncrements($name) : $table->bigInteger($name);
                break;
            case 'SMALLINT':
                $col = $table->smallInteger($name);
                break;
            case 'TINYINT':
                $col = $table->tinyInteger($name);
                break;
            case 'FLOAT':
                $col = $table->float($name);
                break;
            case 'DOUBLE':
                $col = $table->double($name);
                break;
            case 'DECIMAL':
                $bits = is_string($constraint) ? explode(',', $constraint) : [10, 2];
                $col = $table->decimal($name, (int) ($bits[0] ?? 10), (int) ($bits[1] ?? 2));
                break;
            case 'BOOLEAN':
            case 'BOOL':
                $col = $table->boolean($name);
                break;
            case 'DATETIME':
                $col = $table->dateTime($name);
                break;
            case 'DATE':
                $col = $table->date($name);
                break;
            case 'TIME':
                $col = $table->time($name);
                break;
            case 'TIMESTAMP':
                $col = $table->timestamp($name);
                break;
            case 'ENUM':
                $col = $table->enum($name, is_array($constraint) ? $constraint : [$constraint]);
                break;
            default:
                $col = $table->string($name, 255);
                break;
        }

        if ($unsigned && method_exists($col, 'unsigned')) {
            $col->unsigned();
        }
        if ($nullable) {
            $col->nullable();
        }
        if ($hasDefault && $default !== null) {
            $col->default($default);
        }
        if ($change && method_exists($col, 'change')) {
            $col->change();
        }
    }
}

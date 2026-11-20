<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Controller dasar dengan helper bersama untuk seluruh controller aplikasi.
 *
 * Properti yang tersedia:
 *   - $this->request  : Illuminate\Http\Request (current request)
 *   - $this->session  : SessionStore (akses session Laravel)
 *   - $this->response : ResponseBuilder (builder respons JSON/HTML)
 *
 * Helper yang tersedia: isLoggedIn, getUserId, getUserRole, inGroup,
 * isAdmin/isGuru/isKepsek/isSiswa, requireLogin, requireRole,
 * respond, getUser, showError, renderView, maybe_unserialize, getTpSmt.
 */
class BaseController extends Controller
{
    /**
     * Request pengganti khusus (mis. CronControllerController yang membuat
     * request sintetis). Bila null, $this->request selalu request aktif.
     */
    protected ?Request $requestOverride = null;

    /** @var SessionStore */
    protected $session;

    /** @var ResponseBuilder */
    protected $response;

    /** @var Connection|null */
    protected $db;

    protected AuthModel $authModel;

    /** @var \Illuminate\Contracts\Validation\Validator|null */
    protected $validator;

    protected array $validationErrors = [];

    public function __construct()
    {
        // Kunci timezone aplikasi: seluruh logika presensi memakai Asia/Makassar.
        date_default_timezone_set('Asia/Makassar');

        // $this->request tidak di-cache di sini: router meng-cache instance
        // controller, jadi cache request bisa basi pada request berikutnya
        // (mis. dua request dalam satu feature test). Lihat __get().
        $this->session = new SessionStore;
        $this->response = new ResponseBuilder;

        try {
            $this->authModel = new AuthModel;
        } catch (\Throwable $e) {
            // Database connection failed - will be handled by controllers
        }
    }

    // ------------------------------------------------------------------
    // ------------------------------------------------------------------

    protected function isLoggedIn(): bool
    {
        return auth()->check();
    }

    protected function getUserId(): ?int
    {
        $user = auth()->user();

        return $user === null ? null : (int) $user->getAuthIdentifier();
    }

    protected function getUserRole(): ?string
    {
        return auth()->user()?->role_name;
    }

    protected function inGroup(string|array $group): bool
    {
        $role = strtolower((string) (auth()->user()?->role_name ?? ''));
        $norm = static function (string|int $value): string {
            return strtolower((string) $value);
        };
        if (is_array($group)) {
            $group = array_map($norm, $group);

            return in_array($role, $group, true);
        }

        return $role === $norm($group);
    }

    protected function isAdmin(): bool
    {
        return $this->inGroup('admin') || (int) (auth()->user()?->role ?? 0) === 1;
    }

    protected function isGuru(): bool
    {
        return $this->inGroup('guru') || (int) (auth()->user()?->role ?? 0) === 2;
    }

    protected function isKepsek(): bool
    {
        return is_kepsek($this->getUserId() ?? 0);
    }

    protected function isSiswa(): bool
    {
        return $this->inGroup('siswa') || (int) (auth()->user()?->role ?? 0) === 3;
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
        $idMap = ['admin' => 1, 'guru' => 2, 'siswa' => 3];
        $roleId = (int) (auth()->user()?->role ?? 0);
        foreach ($roles as $r) {
            $key = strtolower((string) $r);
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
        return asset('').'assets/';
    }

    protected function getUser(): ?object
    {
        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        // Jika first_name sudah berisi spasi (nama lengkap tersimpan di
        // salah field), jangan tambahkan last_name agar nama tidak ganda.
        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        if (! empty($first) && strpos($first, ' ') !== false) {
            $user->nama_lengkap = $first;
        } elseif ($first === $last) {
            $user->nama_lengkap = $first;
        } else {
            $user->nama_lengkap = trim($first.' '.$last);
        }

        return $user;
    }

    protected function showError(string $message, int $statusCode = 500): never
    {
        abort($statusCode, $message);
    }

    protected function renderView(string $view, array $data = [])
    {
        $data['appUrl'] = url('/');
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
    // ------------------------------------------------------------------

    protected function getSettingRow(): ?object
    {
        try {
            if (! Schema::hasTable('setting')) {
                return null;
            }
            $setting = DB::table('setting')->first();
        } catch (\Throwable $e) {
            return null;
        }

        if ($setting) {
            if (isset($setting->sekolah) && ! isset($setting->nama_sekolah)) {
                $setting->nama_sekolah = $setting->sekolah;
            }
            if (isset($setting->logo_kiri) && ! isset($setting->logo)) {
                $setting->logo = $setting->logo_kiri;
            }
            if (isset($setting->web) && ! isset($setting->website)) {
                $setting->website = $setting->web;
            }
        }

        return $setting;
    }

    /**
     * tabel `setting`, atau insert bila belum ada.
     */
    protected function updateSettingRow(array $data): bool
    {
        if (empty($data)) {
            // Tidak ada kolom yang perlu diperbarui — tidak ada yang bisa gagal.
            return true;
        }

        try {
            if (! Schema::hasTable('setting')) {
                return false;
            }
            $setting = DB::table('setting')->first();
            if ($setting) {
                $idCol = isset($setting->id_setting) ? 'id_setting' : 'id';
                // update() mengembalikan jumlah baris yang benar-benar berubah.
                // Nilai 0 terjadi ketika semua nilai yang dikirim identik dengan
                // yang sudah ada di database — bukan kegagalan, bukan error.
                DB::table('setting')->where($idCol, $setting->{$idCol})->update($data);

                return true;
            }

            return DB::table('setting')->insert($data);
        } catch (\Throwable $e) {
            Log::error('[BaseController::updateSettingRow] '.$e->getMessage());

            return false;
        }
    }

    protected function saveAuditLog(int $aktivitas, string $keterangan = '', ?int $userId = null): bool
    {
        $userId = $userId ?? (int) (auth()->id() ?? 0);
        if (! $userId) {
            return false;
        }

        try {
            if (! Schema::hasTable('logs')) {
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
     * ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => x].
     */
    protected function addTableColumns(string $table, array $fields): void
    {
        try {
            if (! Schema::hasTable($table)) {
                return;
            }
            $existing = Schema::getColumnListing($table);
            $toAdd = array_diff_key($fields, array_flip($existing));
            if (empty($toAdd)) {
                return;
            }
            Schema::table($table, function (Blueprint $t) use ($toAdd) {
                foreach ($toAdd as $name => $def) {
                    $this->addColumnDefinition($t, $name, $def);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[BaseController::addTableColumns] '.$e->getMessage());
        }
    }

    protected function addColumnDefinition(Blueprint $table, string $name, array $def): void
    {
        $type = strtoupper((string) ($def['type'] ?? 'VARCHAR'));
        $constraint = $def['constraint'] ?? null;
        $nullable = ! empty($def['null']);
        $default = $def['default'] ?? null;
        $hasDefault = array_key_exists('default', $def);
        $unsigned = ! empty($def['unsigned']);
        $autoIncrement = ! empty($def['auto_increment']);

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
    // ------------------------------------------------------------------

    /**
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
                        $messages[$field.'.'.$rule] = $msg;
                    }
                }
            } else {
                $ruleStr = $spec;
            }
            $laravelRules[$field] = (string) $ruleStr;
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

    protected function validation(): RequestValidation
    {
        return new RequestValidation($this->validator, $this->validationErrors);
    }

    /**
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

    protected function authUser(?int $id = null): ?object
    {
        try {
            return $this->authModel()->user($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function authModel(): AuthModel
    {
        return $this->authModel ?? new AuthModel;
    }

    protected function authIsExist(string $username): ?object
    {
        try {
            return $this->authModel()->isExist($username);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Hanya membuat; bila username sudah dipakai, kembalikan 0 tanpa menulis.
     */
    protected function authRegister(array $data, string $group): int
    {
        try {
            return $this->authModel()->register($data, $group);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function authDeleteUser(int $id): bool
    {
        try {
            return $this->authModel()->deleteUser($id);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function authRenameUser(int $userId, string $newUsername): array
    {
        try {
            return $this->authModel()->renameUser($userId, $newUsername);
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => 'Gagal mengganti username — tidak ada yang berubah.'];
        }
    }

    protected function authGroups(): array
    {
        try {
            return $this->authModel()->groups();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function authChangePassword(string $username, string $oldPassword, string $newPassword): bool
    {
        try {
            return $this->authModel()->changePassword($username, $oldPassword, $newPassword);
        } catch (\Throwable $e) {
            Log::error('[BaseController::authChangePassword] '.$e->getMessage());

            return false;
        }
    }

    protected function authResetErrorLogin(string $username): bool
    {
        try {
            return $this->authModel()->resetErrorLogin($username);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function updateMasterData(string $table, array $data, string $pk, $id)
    {
        try {
            // DB::update() returns the number of affected rows (int).
            // 0 means no rows changed (data identical) — that is still a
            // success, so we cast to bool only after ruling out false itself.
            $result = DB::table($table)->where($pk, $id)->update($data);

            // false = exception path (never reached here), 0 or more = ok.
            return $result !== false;
        } catch (\Throwable $e) {
            Log::error('[BaseController::updateMasterData] '.$e->getMessage());

            return false;
        }
    }

    public function __get($key)
    {
        if ($key === 'request') {
            return $this->requestOverride ?? request();
        }

        trigger_error('Undefined property: '.static::class.'::$'.$key, E_USER_WARNING);

        return null;
    }

    public function __set($key, $value): void
    {
        if ($key === 'request' && $value instanceof Request) {
            $this->requestOverride = $value;

            return;
        }

        $this->{$key} = $value;
    }
}

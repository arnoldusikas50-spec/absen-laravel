<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: users
 *
 * Handles user authentication, login attempts, and user management
 */
class AuthModel extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'ip_address',
        'username',
        'password',
        'email',
        'activation_code',
        'forgotten_password_code',
        'forgotten_password_time',
        'remember_code',
        'created_on',
        'last_login',
        'active',
        'first_name',
        'last_name',
        'company',
        'phone',
        'foto',
        'nomor_peserta',
    ];

    public $timestamps = false;

    /**
     * Cek apakah pengguna sedang diautentikasi.
     */
    public function loggedIn(): bool
    {
        return auth()->check();
    }

    /**
     * Cek grup pengguna saat ini berdasarkan role_name dari accessor User.
     */
    public function inGroup(string|array $group): bool
    {
        $role = strtolower((string) (auth()->user()?->role_name ?? ''));
        $norm = static function ($g): string {
            return strtolower((string) $g);
        };
        if (is_array($group)) {
            $group = array_map($norm, $group);

            return in_array($role, $group, true);
        }

        return $role === $norm($group);
    }

    /**
     * Cek apakah pengguna saat ini adalah admin.
     */
    public function isAdmin(): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        return strtolower((string) $user->role_name) === 'admin' || (int) $user->role === 1;
    }

    /**
     * Ambil data user berdasarkan ID, atau user yang sedang login.
     */
    public function user(?int $id = null): ?object
    {
        $currentUser = auth()->user();
        $userId = $id ?? ($currentUser === null ? 0 : (int) $currentUser->getAuthIdentifier());
        if ($userId <= 0) {
            return null;
        }

        $user = $currentUser !== null && (int) $currentUser->getAuthIdentifier() === $userId
            ? $currentUser
            : static::find($userId);

        if ($user) {
            $user->nama_lengkap = $user->first_name === $user->last_name
                ? $user->first_name
                : trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }

        return $user;
    }

    /**
     * Get user profile
     */
    public function userProfile(int $userId): ?object
    {
        return static::find($userId);
    }

    /**
     * Get all profiles by user IDs
     */
    public function allProfiles(array $userIds): array
    {
        return static::query()->whereIn('id', $userIds)->get()->all();
    }

    /**
     * Buat akun login baru.
     *
     * HANYA membuat. Bila username sudah dipakai, fungsi ini mengembalikan 0
     * tanpa menulis apa pun: versi lama mengubah baris akun yang sudah ada
     * — termasuk password dan grup — sehingga seorang admin yang mengetik
     * username orang lain di form "tambah panitia/admin" langsung mengambil
     * alih akun itu (password diganti, guru/admin turun jadi grup lain).
     * users_groups juga ditulis ulang semua barisnya, yang menabrak UNIQUE
     * (user_id, group_id) bila akun punya dua grup.
     *
     * Transaksinya melindungi dua tulisannya. Semua pemanggil saat ini
     * (Useradmin/Userguru/Usersiswa/Userpanitia) memanggil tanpa transaksi
     * sendiri. BaseController::authRegister() mendelegasikan ke sini.
     */
    public function register(array $data, string $group): int
    {
        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '') {
            Log::error('[AuthModel::register] username kosong — akun tidak dibuat');

            return 0;
        }
        $data['username'] = $username;

        if ($this->isExist($username)) {
            Log::error('[AuthModel::register] username "'.$username.'" sudah dipakai — tidak ada yang diubah');

            return 0;
        }

        $data['ip_address'] = (string) (request()->ip() ?? '');
        $data['created_on'] = time();
        $data['active'] = '1';

        unset($data['id']);
        $data = array_intersect_key($data, array_flip($this->getFillable()));

        DB::beginTransaction();
        try {
            $id = (int) DB::table('users')->insertGetId($data);
            if ($id <= 0) {
                DB::rollBack();
                Log::error('[AuthModel::register] insert users gagal');

                return 0;
            }

            $tertulisGrup = DB::table('users_groups')->insert([
                'user_id' => $id,
                'group_id' => (int) $group,
            ]);
            if ($tertulisGrup === false) {
                DB::rollBack();
                Log::error('[AuthModel::register] insert users_groups gagal (user '.$id.')');

                return 0;
            }

            DB::commit();

            return $id;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AuthModel::register] '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Check if username exists
     */
    public function isExist(string $username): ?object
    {
        return static::query()->where('username', $username)->first();
    }

    /**
     * Ganti username akun bersama baris master yang tersambung, atomik.
     *
     * Join roster ke akun login memakai username (UsersModel::getUserGuru /
     * getUserSiswa, MasterModel), jadi mengganti hanya users.username membuat
     * guru/siswa kehilangan akunnya secara efektif — "aktifkan akun" lalu
     * membuatkan akun KEDUA untuk orang yang sama.
     * BaseController::authRenameUser() mendelegasikan ke sini.
     */
    public function renameUser(int $userId, string $newUsername): array
    {
        $new = trim($newUsername);
        $old = '';

        if ($userId <= 0) {
            return ['status' => false, 'message' => 'Akun tidak valid.'];
        }
        if (strlen($new) < 4 || strlen($new) > 30 || ! preg_match('/^[A-Za-z0-9_.-]+$/', $new)) {
            return ['status' => false, 'message' => 'Username baru tidak valid (4–30 karakter: huruf/angka/titik/strip/underscore).'];
        }

        DB::beginTransaction();
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            if (! $user) {
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
                $ikut += DB::table('master_siswa')->where('username', $old)
                    ->update(['username' => $new]);
            }

            DB::commit();

            return [
                'status' => true,
                'message' => 'Username "'.$old.'" → "'.$new.'"'.($ikut > 0 ? ' (+'.$ikut.' baris master)' : ''),
                'changed' => true,
                'old' => $old,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AuthModel::renameUser#'.$userId.'] '.$e->getMessage());

            return ['status' => false, 'message' => 'Gagal mengganti username — tidak ada yang berubah.'];
        }
    }

    /**
     * Register multiple users
     */
    public function registerAll(array $users, string $group): array
    {
        $inserted = [];

        foreach ($users as $user) {
            $id = $this->register($user, $group);
            $inserted[] = [
                'id' => $id,
                'username' => $user['username'],
            ];
        }

        return $inserted;
    }

    /**
     * Login - get user with role information
     */
    public function login(string $username): ?object
    {
        return DB::table('users as a')
            ->select('a.*', 'b.user_id', 'b.group_id', 'c.id as role', 'c.name as role_name', 'c.description')
            ->join('users_groups as b', 'a.id', '=', 'b.user_id')
            ->join('groups as c', 'b.group_id', '=', 'c.id')
            ->where('a.username', $username)
            ->first();
    }

    /**
     * Record failed login attempt
     */
    public function errorLogin(array $data): bool
    {
        return DB::table('login_attempts')->insert($data);
    }

    /**
     * Percobaan login yang gagal untuk satu username.
     *
     * $dalamDetik membatasi ke jendela terkini. Tanpa jendela, seluruh
     * riwayat ikut dihitung: akun yang pernah gagal 3x seumur hidupnya
     * langsung terkunci oleh SATU kegagalan terbaru.
     */
    public function cekErrorLogin(string $username, ?int $dalamDetik = null): array
    {
        $b = DB::table('login_attempts')->where('login', $username);
        if ($dalamDetik !== null && $dalamDetik > 0) {
            $b->where('time', '>=', time() - $dalamDetik);
        }
        try {
            return $b->orderBy('time', 'ASC')->get()->all();
        } catch (\Throwable $e) {
            Log::error('[AuthModel::cekErrorLogin] query gagal: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetErrorLogin(string $username): bool
    {
        try {
            if (! Schema::hasTable('login_attempts')) {
                return false;
            }

            return (bool) DB::table('login_attempts')
                ->where('login', $username)
                ->delete();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if student is active
     */
    public function isAktif(string $username): ?object
    {
        return DB::table('master_siswa as a')
            ->select('a.id_siswa', 'b.status')
            ->leftJoin('buku_induk as b', 'a.id_siswa', '=', 'b.id_siswa')
            ->where('a.username', $username)
            ->first();
    }

    /**
     * Check username availability
     */
    public function usernameCheck(string $username, ?int $id = null): bool
    {
        $result = static::query()->where('username', $username)->get()->all();

        if ($id === null) {
            return count($result) > 0;
        }

        return count($result) > 0 && $result[0]->id != $id;
    }

    /**
     * Update user data
     */
    public function changeUser(int $id, array $userData): bool
    {
        return (bool) static::query()->where('id', $id)->update($userData);
    }

    /**
     * Hapus akun beserta keanggotaan grupnya, atomik.
     *
     * Versi lama membungkus writes dengan SET FOREIGN_KEY_CHECKS=0 — tidak
     * ada FK yang merujuk users di skema ini, jadi perintah itu cuma
     * melumpuhkan pemeriksaan pada koneksi yang mungkin dipakai query lain
     * sesudahnya. master_guru.id_user juga ikut dinolkan supaya guru tidak
     * menggantung ke id yang sudah tidak ada.
     * BaseController::authDeleteUser() mendelegasikan ke sini.
     */
    public function deleteUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        DB::beginTransaction();
        try {
            $ada = DB::table('users')->where('id', $id)->first();
            if (! $ada) {
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
            Log::error('[AuthModel::deleteUser#'.$id.'] '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get all user groups
     */
    public function groups(): array
    {
        return DB::table('users_groups')->get()->all();
    }

    /**
     * Get groups for specific user
     */
    public function getUsersGroups(int $userId): array
    {
        return DB::table('users_groups as a')
            ->join('groups as b', 'a.group_id', '=', 'b.id')
            ->where('a.user_id', $userId)
            ->get()->all();
    }

    /**
     * Check user level by username
     */
    public function checkLevel(string $username): ?object
    {
        return DB::table('users as a')
            ->select('a.id', 'a.username', 'b.group_id')
            ->join('users_groups as b', 'b.user_id', '=', 'a.id')
            ->where('a.username', $username)
            ->first();
    }

    /**
     * Change user password
     */
    public function changePassword(string $username, string $oldPassword, string $newPassword): bool
    {
        $user = static::query()->where('username', $username)->first();

        if ($user && Hash::check((string) $oldPassword, (string) $user->password)) {
            return (bool) static::query()->where('username', $username)
                ->update(['password' => Hash::make($newPassword)]);
        }

        return false;
    }

    /**
     * Alias snake_case untuk changePassword (dipakai Userguru::editlogin & Useradmin::change_password)
     */
    public function change_password(string $username, string $oldPassword, string $newPassword): bool
    {
        return $this->changePassword($username, $oldPassword, $newPassword);
    }
}

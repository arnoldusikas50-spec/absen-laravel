<?php

namespace App\Libraries;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * RememberLogin — "tetap login" 30 hari untuk aplikasi presensi (PWA).
 * Hanya untuk peran guru dan siswa; admin dikecualikan demi keamanan.
 *
 * Kolom tabel `users` yang dipakai:
 *   - remember_selector: pencari token (plaintext)
 *   - remember_code: hash SHA-256 dari validator (token mentah tidak disimpan)
 *
 * Cookie `remember_presensi` berisi `selector:validator`, HttpOnly + SameSite=Lax,
 * masa berlaku 30 hari. Validator dirotasi setiap kali sesi dipulihkan.
 * Data sesi aplikasi tetap ditulis agar controller dan tampilan yang ada
 * terus bekerja, sementara guard 'web' Laravel menjadi sumber status login.
 */
class RememberLogin
{
    public const COOKIE_NAME = 'remember_presensi';

    public const DAYS = 30;

    /**
     * Terbitkan token remember baru untuk user dan pasang cookie 30 hari.
     */
    public static function issue(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $selector = bin2hex(random_bytes(9));
            $validator = bin2hex(random_bytes(32));

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'remember_selector' => $selector,
                    'remember_code' => hash('sha256', $validator),
                ]);

            self::sendCookie($selector.':'.$validator);
        } catch (\Throwable $e) {
            Log::error('RememberLogin::issue gagal: '.$e->getMessage());
        }
    }

    /**
     * Pulihkan login dari cookie remember bila guard 'web' belum login.
     * Return true jika sesi berhasil dipulihkan.
     */
    public static function restore(): bool
    {
        try {
            if (auth()->check()) {
                return false;
            }

            $raw = request()->cookie(self::COOKIE_NAME);
            if (empty($raw) || strpos($raw, ':') === false) {
                return false;
            }

            [$selector, $validator] = explode(':', $raw, 2);
            if ($selector === '' || $validator === '') {
                return false;
            }

            $user = DB::table('users as a')
                ->select('a.*', 'b.user_id', 'b.group_id', 'c.id as role', 'c.name as role_name')
                ->join('users_groups as b', 'a.id', '=', 'b.user_id')
                ->join('groups as c', 'b.group_id', '=', 'c.id')
                ->where('a.remember_selector', $selector)
                ->first();

            if (! $user || (string) ($user->active ?? '') !== '1') {
                self::clear();

                return false;
            }

            if (empty($user->remember_code) || ! hash_equals((string) $user->remember_code, hash('sha256', $validator))) {
                // Token tidak cocok — kemungkinan dicuri/disalahgunakan: cabut token user ini.
                DB::table('users')->where('id', (int) $user->id)
                    ->update(['remember_selector' => null, 'remember_code' => null]);
                self::clear();

                return false;
            }

            $roleName = strtolower((string) ($user->role_name ?? ''));

            // Remember 30 hari hanya untuk guru & siswa.
            // Admin ditolak demi keamanan (termasuk token lama bila ada).
            if (! in_array($roleName, ['guru', 'siswa'], true)) {
                DB::table('users')->where('id', (int) $user->id)
                    ->update(['remember_selector' => null, 'remember_code' => null]);
                self::clear();

                return false;
            }

            $isGuru = ($roleName === 'guru');
            $idGuru = 0;
            $namaGuru = null;
            $fotoGuru = null;
            if ($isGuru && Schema::hasTable('master_guru')) {
                $guru = DB::table('master_guru')->where('id_user', (int) $user->id)->first();
                if ($guru) {
                    $idGuru = (int) ($guru->id_guru ?? 0);
                    $namaGuru = $guru->nama_guru ?? null;
                    $fotoGuru = $guru->foto ?? null;
                }
            }

            $nama = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            if ($nama === '' && ! empty($namaGuru)) {
                $nama = $namaGuru;
            }

            // Cegah session fixation saat pemulihan otomatis.
            session()->regenerate();

            session()->put([
                'user_id' => (int) $user->id,
                'id_guru' => $idGuru,
                'username' => $user->username,
                'nama' => $nama,
                'email' => $user->email,
                'role' => $user->group_id,
                'role_name' => $user->role_name,
                'foto' => $fotoGuru ?: $user->foto,
                'school_uuid' => $user->company ?? null,
                'is_login' => true,
                'login_variant' => $isGuru ? 'staff' : 'siswa',
            ]);

            $model = User::query()->find((int) $user->id);
            if ($model !== null) {
                auth()->login($model);
            }

            DB::table('users')->where('id', (int) $user->id)->update(['last_login' => time()]);

            // Rotasi validator setiap pemulihan (token sekali pakai bergilir).
            self::issue((int) $user->id);

            return true;
        } catch (\Throwable $e) {
            Log::error('RememberLogin::restore gagal: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Cabut token remember (di DB) dan hapus cookie. Dipanggil saat logout.
     */
    public static function clear(): void
    {
        try {
            $raw = request()->cookie(self::COOKIE_NAME);
            if (! empty($raw) && strpos($raw, ':') !== false) {
                [$selector] = explode(':', $raw, 2);
                if ($selector !== '') {
                    DB::table('users')
                        ->where('remember_selector', $selector)
                        ->update(['remember_selector' => null, 'remember_code' => null]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan — tetap lanjut hapus cookie.
        }

        try {
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));
        } catch (\Throwable $e) {
        }
    }

    /**
     * Keluarkan cookie remember 30 hari.
     */
    private static function sendCookie(string $value): void
    {
        $secure = false;
        try {
            $secure = request()->secure();
        } catch (\Throwable $e) {
        }

        Cookie::queue(Cookie::make(
            self::COOKIE_NAME,
            $value,
            self::DAYS * 24 * 60,
            '/',
            null,
            $secure,
            true,
            false,
            'Lax'
        ));
    }
}

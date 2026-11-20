<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pembatas akses berdasarkan peran pengguna Laravel.
 *
 * Pemakaian: ->middleware('role:admin') atau ->middleware('role:guru,admin').
 * Role dikenali: admin, guru, siswa.
 *
 * Belum login -> 401 JSON untuk AJAX, else redirect route 'login'.
 * Role tidak diizinkan -> 403 JSON untuk AJAX, else redirect 'dashboard'.
 * Tanpa argumen role -> semua request ditolak (fail-closed).
 */
class RoleMiddleware
{
    /** Pemetaan id grup (users_groups) -> nama role. */
    public const ROLE_BY_GROUP_ID = [
        1 => 'admin',
        2 => 'guru',
        3 => 'siswa',
    ];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $allowed = $this->normalizeRoles($roles);

        if ($this->userHasRole($allowed)) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.',
            ], 403);
        }

        return redirect()->route('dashboard')->with('error', 'Akses ditolak.');
    }

    /**
     * Normalisasi daftar role yang diizinkan: lowercase.
     * Tanpa parameter -> daftar kosong, artinya semua request ditolak
     * (fail-closed), supaya `role:` yang lupa diberi argumen tidak
     * diam-diam berubah menjadi "admin only".
     *
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    protected function normalizeRoles(array $roles): array
    {
        return array_values(array_filter(array_map(fn (string $role): string => strtolower(trim($role)), $roles)));
    }

    /**
     * @param  array<int, string>  $allowed
     */
    protected function userHasRole(array $allowed): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        $roleName = strtolower(trim((string) ($user->role_name ?? '')));
        if ($roleName !== '' && in_array($roleName, $allowed, true)) {
            return true;
        }

        $groupId = (int) ($user->role ?? 0);

        return $groupId > 0
            && isset(self::ROLE_BY_GROUP_ID[$groupId])
            && in_array(self::ROLE_BY_GROUP_ID[$groupId], $allowed, true);
    }
}

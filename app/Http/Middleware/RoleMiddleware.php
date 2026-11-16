<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware — port AdminFilter (CI4) ke Laravel 13.
 *
 * Pemakaian: ->middleware('role:admin') atau ->middleware('role:guru,admin').
 * Role dikenali: admin, guru, siswa.
 *
 * Sumber role (mengikuti AuthFilter/AdminFilter CI4):
 *   1. session('role_name') — string, dibanding lowercase.
 *   2. session('role') — id grup (1=admin, 2=guru, 3=siswa).
 *   3. Bila session kosong, fallback ke atribut user yang login
 *      ($user->role_name / $user->role). Catatan: model App\Models\User
 *      belum menyimpan atribut itu, jadi fallback ini praktis jarang terpakai.
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

        if ($this->userHasRole($request, $allowed)) {
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
     */
    protected function normalizeRoles(array $roles): array
    {
        return array_values(array_filter(array_map(fn ($r) => strtolower(trim((string) $r)), $roles)));
    }

    protected function userHasRole(Request $request, array $allowed): bool
    {
        $roleName = strtolower(trim((string) (session('role_name') ?? '')));
        if ($roleName !== '' && in_array($roleName, $allowed, true)) {
            return true;
        }

        $groupId = (int) (session('role') ?? 0);
        if ($groupId > 0 && isset(self::ROLE_BY_GROUP_ID[$groupId])) {
            return in_array(self::ROLE_BY_GROUP_ID[$groupId], $allowed, true);
        }

        // Fallback: atribut user yang login (bila session role belum terisi).
        $user = $request->user();
        if ($user) {
            $userRole = strtolower(trim((string) ($user->role_name ?? '')));
            if ($userRole !== '' && in_array($userRole, $allowed, true)) {
                return true;
            }
            $userGroup = (int) ($user->role ?? 0);
            if ($userGroup > 0 && isset(self::ROLE_BY_GROUP_ID[$userGroup])) {
                return in_array(self::ROLE_BY_GROUP_ID[$userGroup], $allowed, true);
            }
        }

        return false;
    }
}

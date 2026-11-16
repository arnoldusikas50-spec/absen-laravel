<?php

namespace App\Http\Middleware;

use App\Libraries\RememberLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * RestoreRememberLogin — port logika pemulihan remember-me + revalidasi
 * role 60 detik dari AuthFilter (CI4) ke Laravel 13.
 *
 * Middleware 'auth' bawaan Laravel tidak punya titik hook untuk logika ini
 * tanpa mengubah bootstrap/app.php, jadi logika itu dipisah ke middleware
 * ini. Middleware ini terdaftar pada grup 'web' di bootstrap/app.php,
 * sehingga berlaku untuk seluruh rute web.
 *
 * Perilaku:
 *   1. Bila belum login, coba RememberLogin::restore() (cookie 30 hari,
 *      hanya guru & siswa; admin ditolak + token dicabut).
 *   2. Bila sudah login, revalidasi role dari DB tiap 60 detik: bila akun
 *      tidak ada / tidak aktif, session di-invalidate dan user diarahkan
 *      ke route 'login' (401 JSON untuk AJAX); bila role berubah, session
 *      disinkronkan (role_name / role).
 */
class RestoreRememberLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() && ! session()->get('is_login')) {
            RememberLogin::restore();
        }

        if (session()->get('is_login')) {
            $this->revalidateRole($request);
        }

        return $next($request);
    }

    protected function revalidateRole(Request $request): void
    {
        $lastCheck = (int) (session()->get('role_check_at') ?? 0);
        if (time() - $lastCheck < 60) {
            return;
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            $row = DB::table('users as a')
                ->select('a.active', 'c.name as role_name', 'b.group_id')
                ->join('users_groups as b', 'a.id', '=', 'b.user_id')
                ->join('groups as c', 'b.group_id', '=', 'c.id')
                ->where('a.id', $userId)
                ->first();
        } catch (\Throwable $e) {
            Log::error('RestoreRememberLogin revalidasi gagal: '.$e->getMessage());

            return;
        }

        if (! $row || (string) ($row->active ?? '') !== '1') {
            session()->invalidate();
            auth()->logout();
            if ($request->ajax() || $request->expectsJson()) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'Akun dinonaktifkan.',
                    'redirect' => route('login'),
                ], 401));
            }
            redirect()->route('login')->with('error', 'Akun dinonaktifkan.')->send();
            exit;
        }

        session()->put([
            'role_name' => $row->role_name,
            'role' => $row->group_id,
            'role_check_at' => time(),
        ]);
    }
}

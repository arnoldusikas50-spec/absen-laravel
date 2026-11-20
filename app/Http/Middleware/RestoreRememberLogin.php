<?php

namespace App\Http\Middleware;

use App\Libraries\RememberLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pemulihan "tetap login" dan revalidasi akun.
 *
 * Terdaftar pada grup middleware 'web' di bootstrap/app.php.
 *
 * Perilaku:
 *   1. Bila guard 'web' belum login, coba RememberLogin::restore() (cookie
 *      30 hari, hanya guru & siswa; admin ditolak dan token dicabut).
 *   2. Bila sudah login, status akun dan perannya diverifikasi ulang dari
 *      database tiap 60 detik. Akun nonaktif menyebabkan logout dan redirect
 *      ke route 'login' (401 JSON untuk AJAX).
 */
class RestoreRememberLogin
{
    private const REVALIDATION_INTERVAL = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            RememberLogin::restore();
        }

        if (auth()->check()) {
            $response = $this->revalidateRole($request);
            if ($response !== null) {
                return $response;
            }
        }

        return $next($request);
    }

    protected function revalidateRole(Request $request): ?Response
    {
        $lastCheck = (int) session()->get('role_checked_at', 0);
        if (time() - $lastCheck < self::REVALIDATION_INTERVAL) {
            return null;
        }

        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        try {
            $row = DB::table('users as a')
                ->select('a.active', 'c.name as role_name', 'b.group_id')
                ->join('users_groups as b', 'a.id', '=', 'b.user_id')
                ->join('groups as c', 'b.group_id', '=', 'c.id')
                ->where('a.id', $user->getAuthIdentifier())
                ->first();
        } catch (\Throwable $e) {
            Log::error('RestoreRememberLogin revalidasi gagal: '.$e->getMessage());

            return null;
        }

        if ($row === null || (string) ($row->active ?? '') !== '1') {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akun dinonaktifkan.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Akun dinonaktifkan.');
        }

        session()->put('role_checked_at', time());

        return null;
    }
}

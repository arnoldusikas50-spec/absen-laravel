<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

/**
 * Penanganan error di luar alur controller biasa (dipakai 404 override di CI4).
 *
 * Sengaja tidak memakai BaseController: jalur error tidak boleh ikut
 * menyeret session, model, atau koneksi database yang justru bisa jadi
 * sumber gagalnya request.
 *
 * Catatan porting: di Laravel, 404 ditangani exception handler
 * (bootstrap/app.php — di luar cakupan tahap ini), jadi method ini tidak
 * didaftarkan sebagai rute; ia dipertahankan sebagai utilitas agar kode
 * yang memanggilnya tetap bekerja.
 */
class ErrorsController extends Controller
{
    public function notFound(?string $message = null)
    {
        $request = request();

        if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => false,
                'message' => 'Endpoint tidak ditemukan.',
            ], 404);
        }

        return response()->view('errors.html.error_404', [
            'message' => app()->environment() !== 'production' ? $message : null,
        ], 404);
    }
}

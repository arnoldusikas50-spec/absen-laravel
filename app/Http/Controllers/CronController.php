<?php

namespace App\Http\Controllers;

use App\Libraries\DapodikClient;
use App\Services\DapodikSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CronController extends BaseController
{
    /**
     * Sinkronisasi Dapodik otomatis via HTTP (untuk crontab server atau web-cron).
     *
     * Contoh crontab:
     *   * /30 * * * * curl -s "https://absensi.ikas.my.id/cron/dapodik_sync?key=SECRET_KEY" > /dev/null
     *
     * Hanya berjalan jika 'Auto Sync' aktif di pengaturan Integrasi Dapodik.
     */
    public function dapodik_sync()
    {
        // Validasi secret key — mencegah akses publik sembarangan.
        // Header Authorization: Bearer diutamakan agar key tidak tercatat di
        // URL/access log; query tetap diterima untuk crontab lama.
        $key = $this->request->query('key');
        if ($key === null) {
            $authHeader = $this->request->header('Authorization');
            if ($authHeader !== null && str_starts_with($authHeader, 'Bearer ')) {
                $key = trim(substr($authHeader, 7));
            }
        }
        $raw = env('CRON_SECRET_KEY');
        $raw = is_string($raw) ? trim($raw) : '';

        if ($raw === '') {
            // Fail-closed: tanpa key, jangan pernah menerima nilai tebakan
            // publik. Endpoint cron mengubah data absensi/poin.
            Log::critical('[Cron::dapodik_sync] CRON_SECRET_KEY kosong di .env — request ditolak.');

            return $this->response->statusCode(403)->json(['status' => false, 'message' => 'Forbidden']);
        }

        if (! hash_equals($raw, (string) $key)) {
            return $this->response->statusCode(403)->json(['status' => false, 'message' => 'Forbidden']);
        }

        $db = DB::connection();
        $autoSync = 0;
        if (Schema::hasTable('dapodik_settings')) {
            $autoSync = (int) ($db->table('dapodik_settings')->get()->map(fn ($r) => (array) $r)->first()['auto_sync'] ?? 0);
        }

        if (! $autoSync) {
            return $this->response->json([
                'status' => false,
                'message' => 'Auto-sync Dapodik nonaktif.',
            ]);
        }

        try {
            $client = new DapodikClient;
            $service = new DapodikSyncService;
            $result = $service->syncAllFromApi($client, null);

            return $this->response->json($result);
        } catch (\Throwable $e) {
            Log::error('[Cron::dapodik_sync] Gagal: '.$e->getMessage());

            return $this->response->statusCode(500)->json([
                'status' => false,
                'message' => 'Sinkronisasi Dapodik gagal — periksa log aplikasi.',
            ]);
        }
    }
}

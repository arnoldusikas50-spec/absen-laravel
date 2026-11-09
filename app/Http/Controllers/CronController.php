<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Libraries\DapodikClient;
use App\Services\DapodikSyncService;

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
        // Validasi secret key — mencegah akses publik sembarangan
        $key       = $this->request->query('key') ?? '';
        $raw       = env('CRON_SECRET_KEY');
        $raw       = is_string($raw) ? trim($raw) : '';

        if ($raw === '') {
            // Fail-closed: tanpa key, jangan pernah menerima nilai tebakan
            // publik. Endpoint cron mengubah data absensi/poin.
            Log::critical('[Cron::dapodik_sync] CRON_SECRET_KEY kosong di .env — request ditolak.');
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Forbidden']);
        }

        if (! hash_equals($raw, (string) $key)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Forbidden']);
        }

        $db = \Illuminate\Support\Facades\DB::connection();
        $autoSync = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('dapodik_settings')) {
            $autoSync = (int) ($db->table('dapodik_settings')->get()->map(fn($r) => (array) $r)->first()['auto_sync'] ?? 0);
        }

        if (!$autoSync) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Auto-sync Dapodik nonaktif.',
            ]);
        }

        try {
            $client  = new DapodikClient();
            $service = new DapodikSyncService();
            $result  = $service->syncAllFromApi($client, null);

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            Log::error('[Cron::dapodik_sync] Gagal: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status'  => false,
                'message' => 'Sinkronisasi Dapodik gagal — periksa log aplikasi.',
            ]);
        }
    }
}

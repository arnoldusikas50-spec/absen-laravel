<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
/**
 * Health endpoint publik untuk monitoring (load balancer / cron eksternal).
 * GET /healthz → 200 {"status":"ok",...} atau 503 bila ada komponen gagal.
 * Sengaja tidak menampilkan info sensitif.
 */
class HealthController extends BaseController
{
    public function index()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'writable' => $this->checkWritable(),
            'disk'     => $this->checkDisk(),
        ];

        $ok = !in_array(false, [$checks['database'], $checks['writable'], $checks['disk']], true);

        return $this->response
            ->setStatusCode($ok ? 200 : 503)
            ->setContentType('application/json')
            ->setJSON([
                'status' => $ok ? 'ok' : 'fail',
                'checks' => $checks,
                'time'   => date('Y-m-d H:i:s'),
            ]);
    }

    public function checkDatabase(): bool
    {
        try {
            DB::connection()->select('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            Log::error('[health] database: ' . $e->getMessage());
            return false;
        }
    }

    public function checkWritable(): bool
    {
        $dir = storage_path('logs');
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        // Uji tulis nyata agar permission/disk penuh ketahuan sebelum request
        // user gagal — tapi endpoint ini publik dan tanpa auth, jadi hasilnya
        // di-cache 60 detik supaya setiap hit tidak membuat & menghapus file.
        $tersimpan = Cache::get('health_writable_probe');
        if ($tersimpan !== null) {
            return (bool) $tersimpan;
        }

        $probe = $dir . '/.health_probe';
        $wrote = @file_put_contents($probe, 'ok') !== false;
        if ($wrote) {
            @unlink($probe);
        }
        Cache::put('health_writable_probe', $wrote, 60);

        return $wrote;
    }

    public function checkDisk(): bool
    {
        try {
            $free = @disk_free_space(storage_path());
            return $free === false ? true : $free > 200 * 1024 * 1024;
        } catch (\Throwable $e) {
            return true;
        }
    }
}

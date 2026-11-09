<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Porting CI4 App\Commands\HealthCheck (php spark health:check).
 *
 * Cek kesehatan aplikasi dari cron server dan kirim alert Telegram bila gagal.
 *
 *   php artisan health:check
 *
 * Pemeriksaan (disalin dari CI4 App\Controllers\Health::checkDatabase(),
 * ::checkWritable(), ::checkDisk() — controller itu belum di-port, jadi
 * logikanya di-inline di sini agar command tetap mandiri):
 *   database : SELECT 1 berhasil
 *   writable : storage/logs ada, bisa ditulis, + probe file (cache 60 dtk)
 *   disk     : sisa ruang storage/ > 200 MB
 *
 * Dijadwalkan tiap 10 menit — lihat routes/console.php.
 */
class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Cek database/writable/disk, kirim Telegram bila gagal';

    public function handle(): int
    {
        $results = [
            'database' => $this->checkDatabase(),
            'writable' => $this->checkWritable(),
            'disk' => $this->checkDisk(),
        ];

        $failed = array_keys(array_filter($results, static fn ($ok) => ! $ok));

        if (empty($failed)) {
            $this->info('OK: database, writable, disk sehat');

            return self::SUCCESS;
        }

        $msg = '🚨 <b>HEALTH CHECK GAGAL</b>'."\n"
            .'Komponen: '.implode(', ', $failed)."\n"
            .'Waktu: '.date('d/m/Y H:i:s')."\n"
            .'Segera periksa server absensi.';

        try {
            // Fallback WA bila TG mati: coba keduanya via method publik.
            $notif = new NotificationService();
            $sentTg = $notif->sendTelegram($msg);
            $sentWa = false;
            try {
                $sentWa = $notif->sendWhatsApp($msg);
            } catch (\Throwable $e2) {
                $sentWa = false;
            }
            $sent = $sentTg || $sentWa;
            $this->line($sent ? 'Alert terkirim (TG/WA)' : 'Gagal kirim alert TG+WA', $sent ? 'info' : 'error');
        } catch (\Throwable $e) {
            $this->error('Alert gagal: '.$e->getMessage());
        }

        $this->error('FAIL: '.implode(', ', $failed));
        // Exit non-zero agar pola `curl --fail || php artisan health:check`
        // dan monitoring cron bisa deteksi gagal (sebelumnya selalu exit 0).
        return self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable $e) {
            Log::error('[health] database: '.$e->getMessage());

            return false;
        }
    }

    private function checkWritable(): bool
    {
        $dir = storage_path('logs');
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }

        // Uji tulis nyata agar permission/disk penuh ketahuan sebelum request
        // user gagal — hasilnya di-cache 60 detik supaya setiap hit tidak
        // membuat & menghapus file.
        $tersimpan = Cache::get('health_writable_probe');
        if ($tersimpan !== null) {
            return (bool) $tersimpan;
        }

        $probe = $dir.'/.health_probe';
        $wrote = @file_put_contents($probe, 'ok') !== false;
        if ($wrote) {
            @unlink($probe);
        }
        Cache::put('health_writable_probe', $wrote, 60);

        return $wrote;
    }

    private function checkDisk(): bool
    {
        try {
            $free = @disk_free_space(storage_path());

            return $free === false ? true : $free > 200 * 1024 * 1024;
        } catch (\Throwable $e) {
            return true;
        }
    }
}

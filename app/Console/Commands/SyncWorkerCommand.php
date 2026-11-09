<?php

namespace App\Console\Commands;

use App\Libraries\SyncService;
use Illuminate\Console\Command;

/**
 * Porting CI4 App\Commands\SyncWorker (php spark sync:worker).
 *
 * Menjalankan worker sinkronisasi lokal-pusat secara kontinu atau sekali jalan.
 *
 *   php artisan sync:worker [--once] [--sleep=5]
 *
 * Worker daemon: TIDAK dijadwalkan lewat Laravel Scheduler (ia berjalan
 * kontinu sendiri); jalankan sebagai proses latar / service systemd.
 */
class SyncWorkerCommand extends Command
{
    protected $signature = 'sync:worker {--once} {--sleep=5}';

    protected $description = 'Menjalankan worker sinkronisasi lokal-pusat secara kontinu atau sekali jalan';

    public function handle(): int
    {
        $once = (bool) $this->option('once');
        $sleep = max(1, (int) ($this->option('sleep') ?? 5));

        $service = new SyncService();

        $this->warn('=== Sync Worker ===');
        $this->line('Mode: '.($once ? 'single job' : 'daemon'));
        $this->line('Sleep interval: '.$sleep.' detik');

        try {
            $service->runWorker($once, $sleep);
        } catch (\Throwable $e) {
            $this->error('Worker error: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

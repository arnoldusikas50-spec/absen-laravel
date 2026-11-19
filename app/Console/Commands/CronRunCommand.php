<?php

namespace App\Console\Commands;

use App\Http\Controllers\CronControllerController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Menjalankan job /cron/* yang belum punya padanan Artisan command,
 * langsung di dalam proses scheduler (tanpa HTTP + tanpa CRON_SECRET_KEY).
 */
class CronRunCommand extends Command
{
    protected $signature = 'cron:run
        {job : daily, rekap-piket, mark-alpha, poin-siswa, semester-recap, weekly-denda, monthly-pdf, ucapan-pagi, ucapan-sore, apel-kesadaran}
        {--tanggal= : Tanggal YYYY-MM-DD untuk job yang mendukung}
        {--bulan= : Bulan periode (1-12)}
        {--tahun= : Tahun periode}
        {--semester= : Semester (1|2)}
        {--force : Kirim ulang walau periode sudah tercatat}';

    protected $description = 'Jalankan job cron presensi in-process (pengganti curl /cron/*)';

    private const JOBS = [
        'daily' => 'dailyProcess',
        'rekap-piket' => 'rekapPiket',
        'mark-alpha' => 'markAlpha',
        'poin-siswa' => 'poinSiswa',
        'semester-recap' => 'semesterRecap',
        'weekly-denda' => 'weeklyDendaRecap',
        'monthly-pdf' => 'monthlyPdf',
        'ucapan-pagi' => 'ucapanPagi',
        'ucapan-sore' => 'ucapanSore',
        'apel-kesadaran' => 'apelKesadaran',
    ];

    public function handle(): int
    {
        $job = (string) $this->argument('job');
        $method = self::JOBS[$job] ?? null;
        if ($method === null) {
            $this->error('Job tidak dikenal: '.$job.'. Pilihan: '.implode(', ', array_keys(self::JOBS)));

            return self::FAILURE;
        }

        $params = [];
        foreach (['tanggal', 'bulan', 'tahun', 'semester'] as $option) {
            $value = $this->option($option);
            if ($value !== null && $value !== '') {
                $params[$option] = (string) $value;
            }
        }
        if ($this->option('force')) {
            $params['force'] = '1';
        }

        try {
            $response = app(CronControllerController::class)->runInternal($method, $params);
        } catch (\Throwable $e) {
            Log::error('[cron:run] '.$job.' gagal: '.$e->getMessage());
            $this->error('Job '.$job.' gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $status = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;
        $body = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        $this->line($body !== '' ? $body : 'OK');

        return $status >= 400 ? self::FAILURE : self::SUCCESS;
    }
}

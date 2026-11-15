<?php

namespace App\Console\Commands;

use App\Http\Controllers\CronControllerController;
use App\Services\HariSekolahService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command cron ucapan selamat ulang tahun untuk guru yang berulang tahun.
 *
 *   php artisan cron:ulang-tahun
 *   php artisan cron:ulang-tahun --tanggal=2026-11-15 --force
 */
class CronUlangTahunCommand extends Command
{
    protected $signature = 'cron:ulang-tahun
                            {--tanggal= : Tanggal target (YYYY-MM-DD), default hari ini}
                            {--force : Abaikan penjaga idempotensi (kirim ulang)}';

    protected $description = 'Kirim ucapan selamat ulang tahun untuk guru yang berulang tahun hari ini';

    public function handle(): int
    {
        $tanggal = (string) $this->option('tanggal');
        if (! is_string($tanggal) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1) {
            $tanggal = date('Y-m-d');
        }

        $force = (bool) $this->option('force');

        $notifService = new NotificationService;

        if ($notifService->isAllChannelsDisabled('ulang_tahun')) {
            $this->warn('Ucapan ulang tahun dilewati: seluruh channel notifikasi dinonaktifkan di Pengaturan.');

            return self::SUCCESS;
        }

        if (! $force && $this->sudahTerkirim($tanggal)) {
            $this->info("Ucapan ulang tahun untuk {$tanggal} sudah terkirim sebelumnya, dilewati. Gunakan --force untuk kirim ulang.");

            return self::SUCCESS;
        }

        $cronController = new CronControllerController;
        $guruList = $cronController->getGuruUlangTahun($tanggal);

        if (empty($guruList)) {
            $this->info("Tidak ada guru yang berulang tahun pada tanggal {$tanggal}.");

            return self::SUCCESS;
        }

        $svc = new HariSekolahService;
        $tanggalPanjang = $svc->tanggalPanjang($tanggal);

        $this->line('Ditemukan '.count($guruList)." guru berulang tahun pada {$tanggalPanjang}:");
        foreach ($guruList as $g) {
            $this->line("- {$g->nama_guru}".($g->usia ? " (ke-{$g->usia} tahun)" : ''));
        }

        try {
            $hasil = $notifService->notifyUlangTahun($guruList, $tanggalPanjang);
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim ucapan ulang tahun: '.$e->getMessage());
            Log::error('[CronUlangTahunCommand] Gagal: '.$e->getMessage());
            $this->catatLog($tanggal, false, $e->getMessage());

            return self::FAILURE;
        }

        $daftarNama = array_map(static fn ($g) => $g->nama_guru, $guruList);
        $namaStr = implode(', ', $daftarNama);

        if (! $hasil['grup'] && $hasil['personal'] === 0) {
            $reason = $notifService->lastError !== ''
                ? $notifService->lastError
                : 'Tidak ada pesan yang berhasil dikirim ke grup maupun personal';
            $this->error("Ucapan ulang tahun GAGAL: {$reason}");
            $this->catatLog($tanggal, false, $reason);

            return self::FAILURE;
        }

        $this->catatLog($tanggal, true, 'Guru: '.$namaStr);

        $this->info(sprintf(
            'Berhasil mengirim ucapan ulang tahun untuk %d guru (%s) — Grup: %s, Personal: %d terkirim.',
            count($guruList),
            $namaStr,
            $hasil['grup'] ? 'OK' : 'Dilewati/Gagal',
            $hasil['personal']
        ));

        return self::SUCCESS;
    }

    private function sudahTerkirim(string $periode): bool
    {
        try {
            return DB::table('cron_notifikasi_log')
                ->where('jenis', 'ulang_tahun')
                ->where('periode', $periode)
                ->where('terkirim', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function catatLog(string $periode, bool $terkirim, ?string $catatan = null): void
    {
        $now = date('Y-m-d H:i:s');
        try {
            $row = DB::table('cron_notifikasi_log')
                ->where('jenis', 'ulang_tahun')
                ->where('periode', $periode)
                ->first();

            if ($row) {
                DB::table('cron_notifikasi_log')
                    ->where('id', $row->id)
                    ->update([
                        'terkirim' => $terkirim ? 1 : $row->terkirim,
                        'percobaan' => ((int) $row->percobaan) + 1,
                        'catatan' => $catatan,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('cron_notifikasi_log')->insert([
                    'jenis' => 'ulang_tahun',
                    'periode' => $periode,
                    'terkirim' => $terkirim ? 1 : 0,
                    'percobaan' => 1,
                    'catatan' => $catatan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[CronUlangTahunCommand] Gagal catat log: '.$e->getMessage());
        }
    }
}

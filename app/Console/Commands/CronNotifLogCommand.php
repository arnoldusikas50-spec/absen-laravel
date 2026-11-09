<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\CronNotifLog (php spark cron:notif-log).
 *
 * Riwayat pengiriman notifikasi cron dari tabel cron_notifikasi_log.
 *
 *   php artisan cron:notif-log            # 20 baris terakhir
 *   php artisan cron:notif-log 50         # 50 baris terakhir
 *   php artisan cron:notif-log 30 gagal   # hanya yang gagal
 */
class CronNotifLogCommand extends Command
{
    protected $signature = 'cron:notif-log {jumlah=20} {filter?}';

    protected $description = 'Tampilkan riwayat pengiriman notifikasi cron';

    public function handle(): int
    {
        $limit = (int) $this->argument('jumlah');
        if ($limit < 1) {
            $limit = 20;
        }
        $limit = min($limit, 500);

        $filter = strtolower(trim((string) ($this->argument('filter') ?? '')));
        $terkirim = null;
        if ($filter === 'gagal' || $filter === 'fail') {
            $terkirim = 0;
        } elseif ($filter === 'ok' || $filter === 'sukses') {
            $terkirim = 1;
        }

        if (! Schema::hasTable('cron_notifikasi_log')) {
            $this->error('Tabel cron_notifikasi_log belum ada. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $builder = DB::table('cron_notifikasi_log')
            ->select('jenis', 'periode', 'terkirim', 'percobaan', 'catatan', 'updated_at')
            ->orderBy('updated_at', 'DESC')
            ->limit($limit);

        if ($terkirim !== null) {
            $builder->where('terkirim', $terkirim);
        }

        $rows = $builder->get();

        if ($rows->isEmpty()) {
            $this->warn('Belum ada riwayat pengiriman.');

            return self::SUCCESS;
        }

        $this->line(sprintf('Riwayat notifikasi cron — %d baris (terbaru dulu)', $rows->count()));
        $this->line('');

        foreach ($rows as $r) {
            $status = ((int) $r->terkirim) === 1 ? 'OK   ' : 'GAGAL';
            $waktu = substr((string) $r->updated_at, 0, 16);
            $ket = $r->catatan !== null && $r->catatan !== ''
                ? ' — '.mb_strimwidth((string) $r->catatan, 0, 60, '…')
                : '';

            $this->line(sprintf(
                '%s  %-17s %-22s percobaan=%-3d %s%s',
                $status,
                $r->jenis,
                $r->periode,
                (int) $r->percobaan,
                $waktu,
                $ket
            ));
        }

        $gagal = $rows->filter(static fn ($r) => (int) $r->terkirim !== 1);
        $this->line('');

        if ($gagal->isNotEmpty()) {
            $this->warn($gagal->count().' dari '.$rows->count().' gagal pada tampilan ini.');
        }

        return self::SUCCESS;
    }
}

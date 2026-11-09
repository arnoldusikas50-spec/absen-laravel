<?php

namespace App\Console\Commands;

use App\Libraries\DapodikClient;
use App\Services\DapodikSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\DapodikSync (php spark dapodik:sync).
 *
 * Sinkronisasi data dari Dapodik Web Service via CLI / Cron.
 *
 *   php artisan dapodik:sync [all|siswa|rombel|gtk|mapel|sekolah|jurusan] [--force]
 *
 * Saat dijalankan oleh cron, hormati pengaturan auto_sync (kecuali --force).
 * Per CRONTAB.MD §5, auto-sync Dapodik NONAKTIF pada instalasi ini sehingga
 * command ini sengaja TIDAK dijadwalkan di routes/console.php.
 */
class DapodikSyncCommand extends Command
{
    protected $signature = 'dapodik:sync {entitas=all} {--force}';

    protected $description = 'Sinkronisasi data dari Dapodik Web Service via CLI / Cron';

    public function handle(): int
    {
        $entitas = $this->argument('entitas') ?? 'all';
        $force = (bool) $this->option('force');

        $autoSync = 0;
        $isActive = 0;
        if (Schema::hasTable('dapodik_settings')) {
            $row = DB::table('dapodik_settings')->first();
            $autoSync = (int) ($row->auto_sync ?? 0);
            $isActive = (int) ($row->is_active ?? 0);
        }
        if (! $force && (! $autoSync || ! $isActive)) {
            $this->warn("Auto-sync Dapodik nonaktif. Aktifkan 'Auto Sync' dan 'Integration Web Service' di menu Integrasi Dapodik, atau jalankan dengan --force.");

            return self::SUCCESS;
        }

        $client = new DapodikClient();
        $service = new DapodikSyncService();

        $this->warn("=== Dapodik Sync: {$entitas} ===");

        switch ($entitas) {
            case 'siswa':
                $res = $service->syncSiswaFromApi($client, null);
                break;
            case 'rombel':
                $res = $service->syncRombelFromApi($client, null);
                break;
            case 'gtk':
                $res = $service->syncGtkFromApi($client, null);
                break;
            case 'mapel':
                $res = $service->syncMapelFromApi($client, null);
                break;
            case 'jurusan':
                $rb = $client->getRombonganBelajar();
                $items = [];
                if ($rb['status'] && ! empty($rb['data'])) {
                    $rows = $rb['data']['rows'] ?? $rb['data'];
                    if (is_array($rows)) {
                        $items = $rows;
                    }
                }
                if (empty($items)) {
                    $res = ['status' => false, 'message' => 'Tidak ada data rombel untuk sinkronisasi jurusan.'];
                } else {
                    $res = $service->syncJurusanFromRombel($items, null);
                }
                break;
            case 'sekolah':
                $res = $service->syncSekolahFromApi($client, null);
                break;
            case 'all':
            default:
                $res = $service->syncAllFromApi($client, null);
                break;
        }

        if ($res['status']) {
            $this->info($res['message']);
        } else {
            $this->error($res['message']);
        }

        // Show summary
        $guruCount = Schema::hasTable('master_guru') ? DB::table('master_guru')->count() : 0;
        $siswaCount = Schema::hasTable('master_siswa') ? DB::table('master_siswa')->count() : 0;
        $kelasCount = Schema::hasTable('master_kelas') ? DB::table('master_kelas')->count() : 0;
        $mapelCount = Schema::hasTable('master_mapel') ? DB::table('master_mapel')->count() : 0;

        $this->newLine();
        $this->warn('=== Status Akhir Database Presensi & KBM ===');
        $this->line("Guru: {$guruCount} | Siswa: {$siswaCount} | Kelas: {$kelasCount} | Mapel: {$mapelCount}");

        return $res['status'] ? self::SUCCESS : self::FAILURE;
    }
}

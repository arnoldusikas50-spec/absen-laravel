<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\PresensiAdmin;
use App\Http\Controllers\Admin\PresensiAdminController;
use App\Models\AttendanceSettingModel;
use App\Services\AttendancePenaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cek denda YTD seorang user: denda harian tercatat + missingAlphaFines
 * (logika guru) + statistik YTD admin + denda kotor bulan berjalan.
 *
 * App\Controllers\Admin\PresensiAdmin::getYTDDendaStats() dan
 * command ini dibuat, jadi kedua bagian itu dijalankan hanya bila salah
 * satu kelas kandidat di bawah ini sudah ada; bila belum ada, dilewati
 * dengan peringatan (bukan error).
 */
class CheckDendaCommand extends Command
{
    protected $signature = 'check:denda {username=guruku6}';

    protected $description = 'Cek denda YTD seorang user (param: username, default: guruku6)';

    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));
        if ($username === '') {
            $this->error('Username tidak boleh kosong. Contoh: php artisan check:denda guruku6');

            return self::FAILURE;
        }

        $user = DB::table('users')->where('username', $username)->first();
        if (! $user) {
            $this->error("User {$username} not found");

            return self::FAILURE;
        }

        $this->line("User ID: {$user->id} ({$user->first_name} {$user->last_name})");

        // 1. Denda Harian
        $awalTahun = date('Y-01-01');
        $harian = DB::table('absensi_harian')
            ->where('user_id', $user->id)
            ->where('tgl', '>=', $awalTahun)
            ->where('tgl', '<=', date('Y-m-d'))
            ->selectRaw('SUM(COALESCE(denda, 0)) as total')
            ->first();
        $this->line('Total Denda Harian (recorded): '.($harian->total ?? 0));

        // Denda Harian per bulan
        $harianPerBulan = DB::select(
            'SELECT MONTH(tgl) as bln, SUM(denda) as total_denda, COUNT(*) as cnt
             FROM absensi_harian
             WHERE user_id = ? AND tgl >= ? AND denda > 0
             GROUP BY MONTH(tgl)
             ORDER BY bln ASC',
            [$user->id, $awalTahun]
        );
        $this->line('Denda harian per bulan:');
        foreach ($harianPerBulan as $hp) {
            $this->line("  Bulan {$hp->bln}: {$hp->total_denda} ({$hp->cnt} hari)");
        }

        // 2. Guru Attendance::dendaYtd logic
        $setting = (new AttendanceSettingModel)->first();
        $penaltySvc = new AttendancePenaltyService;
        $startDate = $awalTahun;
        $endDate = date('Y-m-d');
        $missYtd = $penaltySvc->missingAlphaFines((int) $user->id, $startDate, $endDate, $setting, false);

        $this->line('');
        $this->line('--- Guru missingAlphaFines (rentang penuh Jan-today) ---');
        $this->line('Missing count: '.$missYtd['count']);
        $this->line('Missing total denda: '.$missYtd['total']);
        $totalYtdGuru = (float) ($harian->total ?? 0) + (float) ($missYtd['total'] ?? 0);
        $this->line('Guru Total Denda YTD: '.$totalYtdGuru);

        $adminClass = $this->resolveAdminControllerClass();
        if ($adminClass === null) {
            $this->warn('Bagian Admin (getYTDDendaStats / filteredAlphaMonthMap) dilewati: controller PresensiAdmin tidak tersedia.');
        } else {
            $admin = new $adminClass;
            $ref = new \ReflectionClass($admin);

            $method = $ref->getMethod('getYTDDendaStats');
            $method->setAccessible(true);
            $ytdStats = $method->invoke($admin, (int) date('Y'));

            $userYtdAdmin = $ytdStats[$user->id] ?? null;
            $this->line('');
            $this->line('--- Admin getYTDDendaStats result ---');
            if ($userYtdAdmin) {
                $this->line('Admin Total Denda YTD: '.$userYtdAdmin['total_denda']);
                $this->line('Admin Dibayar Tunai:   '.$userYtdAdmin['dibayar_tunai']);
                $this->line('Admin Potongan Poin:   '.$userYtdAdmin['potongan_poin']);
                $this->line('Admin Potongan Saldo:  '.$userYtdAdmin['potongan_saldo']);
                $this->line('Admin Total Dibayar:   '.$userYtdAdmin['total_dibayar']);
                $this->line('Admin Sisa Denda:      '.$userYtdAdmin['sisa']);
            }

            $blnIni = date('m');
            $thnIni = date('Y');
            $this->line('');
            $this->line("--- Cek Bulan Ini ({$blnIni}/{$thnIni}) ---");
            $alphaDetailMap = $ref->getMethod('filteredAlphaMonthMap');
            $alphaDetailMap->setAccessible(true);
            $mMap = $alphaDetailMap->invoke($admin, date('Y-m-01'), date('Y-m-t'), $setting);
            $userSep = $mMap[$user->id] ?? null;
            if ($userSep) {
                $this->line('Sep count: '.$userSep['count']);
                $this->line('Sep missing count: '.$userSep['missing_count']);
                $this->line('Sep total denda missing: '.$userSep['total_denda']);
            }
        }

        // Denda harian bulan berjalan
        $harianBln = DB::table('absensi_harian')
            ->where('user_id', $user->id)
            ->whereMonth('tgl', (int) date('m'))
            ->whereYear('tgl', (int) date('Y'))
            ->selectRaw('SUM(COALESCE(denda, 0)) as total')
            ->first();
        $this->line('Denda Harian Bln Ini: '.($harianBln->total ?? 0));
        $this->line('Denda Kotor Bln Ini Admin = Denda Harian ('.($harianBln->total ?? 0).') + Denda Alpha ('.($userSep['total_denda'] ?? 0).') = '.((float) ($harianBln->total ?? 0) + (float) ($userSep['total_denda'] ?? 0)));

        return self::SUCCESS;
    }

    private function resolveAdminControllerClass(): ?string
    {
        foreach ([
            PresensiAdminController::class,
            PresensiAdmin::class,
        ] as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

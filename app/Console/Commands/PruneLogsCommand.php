<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Porting CI4 App\Commands\PruneLogs (php spark logs:prune).
 *
 * Retensi file log aplikasi.
 *
 *   php artisan logs:prune [--keep=60] [--cron-max-mb=20] [--dry]
 *
 * Yang disentuh HANYA pola nama berkas di storage/logs:
 *   - cron.log  : dirotasi (rename) saat melebihi batas ukuran, tidak dihapus
 *   - log-*.log / log-*.php / cron-*.log / laravel-*.log : dihapus bila
 *     umurnya lewat --keep hari. Pola laravel-*.log adalah padanan Laravel
 *     (log harian default Laravel bernama laravel-YYYY-MM-DD.log) dari
 *     pola log-*.log CI4.
 * Berkas pelindung direktori (index.html, .htaccess), probe health
 * (.health_probe), symlink, dan seluruh isi tabel database tidak pernah disentuh.
 *
 * Cron harian yang disarankan (03:30, setelah backup 02:00) — lihat
 * routes/console.php.
 */
class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune {--keep=60} {--cron-max-mb=20} {--dry}';

    protected $description = 'Rotasi cron.log & hapus file log lama (retensi disk)';

    /** Pola berkas usang yang boleh dihapus. */
    private const POLA_HAPUS = ['log-*.log', 'log-*.php', 'cron-*.log', 'laravel-*.log'];

    public function handle(): int
    {
        $keep = $this->optInt('keep', 60);
        $cronMaksMb = $this->optInt('cron-max-mb', 20);
        $dry = (bool) $this->option('dry');

        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            $this->error('Direktori log tidak ada: '.$dir);

            return self::FAILURE;
        }

        $batas = time() - ($keep * 86400);
        $dihapus = 0;
        $byteDibebaskan = 0;
        $gagal = [];

        // 1. Rotasi cron.log bila membengkak. Nama hasil rotasi ikut pola
        //    cron-*.log, jadi umur --keep berlaku untuknya di langkah 2.
        $cron = $dir.'/cron.log';
        if (is_file($cron) && ! is_link($cron)) {
            $ukur = (int) (@filesize($cron) ?: 0);
            if ($ukur > $cronMaksMb * 1024 * 1024) {
                $tujuan = $this->namaRotasi($dir, $ukur);
                $this->line('cron.log '.round($ukur / 1048576, 1).' MB → rotasi ke '.basename($tujuan));
                if ($dry) {
                    Log::info('[logs:prune] dry-run: akan merotasi cron.log ke '.$tujuan);
                } elseif (! @rename($cron, $tujuan)) {
                    // Jangan pernah men-truncate cron.log: shell cron membuka
                    // berkas ini per panggilan, dan menimpa inode yang sedang
                    // dipakai membuat tulisan berikutnya hilang diam-diam.
                    $gagal[] = 'rotasi cron.log';
                    Log::error('[logs:prune] rotasi cron.log gagal → '.$tujuan);
                }
            }
        }

        // 2. Hapus berkas log usang.
        foreach (self::POLA_HAPUS as $pola) {
            foreach (glob($dir.'/'.$pola) ?: [] as $file) {
                // Symlink tidak pernah dihapus: unlink lewat symlink menembus
                // direktori lain dan mencabut berkas milik orang lain.
                if (! is_file($file) || is_link($file)) {
                    continue;
                }
                $umur = @filemtime($file);
                if ($umur === false || $umur >= $batas) {
                    continue;
                }
                $byte = (int) (@filesize($file) ?: 0);
                if ($dry) {
                    $this->warn('akan dihapus: '.basename($file).' ('.round($byte / 1024, 1).' KB, '.date('Y-m-d', $umur).')');
                    $dihapus++;
                    $byteDibebaskan += $byte;
                    continue;
                }
                if (@unlink($file)) {
                    $dihapus++;
                    $byteDibebaskan += $byte;
                } else {
                    $gagal[] = basename($file);
                }
            }
        }

        $ringkas = sprintf(
            '%s: %d berkas (%s), retensi %d hari%s',
            $dry ? 'DRY-RUN' : 'OK',
            $dihapus,
            $this->fmtByte($byteDibebaskan),
            $keep,
            $gagal === [] ? '' : ', GAGAL: '.implode(', ', $gagal)
        );
        $gagal === [] ? $this->info($ringkas) : $this->error($ringkas);
        Log::log($gagal === [] ? 'info' : 'error', '[logs:prune] '.$ringkas);

        return $gagal === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Opsi integer. Tanpa nilai, non-numerik, atau < 1 tidak boleh diam-diam
     * jadi 1 — retensi 1 hari berarti menghapus hampir semua yang seharusnya
     * disimpan — jadi nilai rusak kembali ke bawaan dengan peringatan.
     * (Padanan CI4 App\Traits\CliOptionTrait::optInt; di Laravel opsi
     * --keep=... sudah dipisah parser Symfony, jadi cukup validasi nilainya.)
     */
    private function optInt(string $nama, int $bawaan): int
    {
        $nilai = $this->option($nama);

        if ($nilai === null || $nilai === true) {
            return $bawaan;
        }

        if (! is_numeric($nilai) || (int) $nilai < 1) {
            $this->warn("--{$nama}=\"{$nilai}\" bukan angka ≥ 1, pakai bawaan {$bawaan}.");

            return $bawaan;
        }

        return (int) $nilai;
    }

    /**
     * Nama tujuan rotasi yang tidak menimpa hasil rotasi sebelumnya.
     */
    private function namaRotasi(string $dir, int $ukur): string
    {
        $dasar = sprintf('%s/cron-%s_%dMB.log', $dir, date('Ymd-His'), round($ukur / 1048576));
        $tujuan = $dasar;
        for ($i = 2; is_file($tujuan) && $i < 100; $i++) {
            $tujuan = sprintf('%s/cron-%s_%dMB-%d.log', $dir, date('Ymd-His'), round($ukur / 1048576), $i);
        }

        return $tujuan;
    }

    private function fmtByte(int $byte): string
    {
        if ($byte >= 1048576) {
            return round($byte / 1048576, 2).' MB';
        }

        return round($byte / 1024, 1).' KB';
    }
}

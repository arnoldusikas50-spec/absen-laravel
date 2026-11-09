<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Porting CI4 App\Commands\DbBackup (php spark db:backup).
 *
 * Backup database MySQL/MariaDB via mysqldump.
 *
 *   php artisan db:backup [--keep=7]
 *
 * Hasil: storage/backups/db_YYYYmmdd_HHMMSS.sql.gz (di LUAR webroot,
 * jangan simpan backup di public/ agar tidak bisa diunduh publik).
 * Cron harian yang disarankan (02:00) — lihat routes/console.php.
 */
class DbBackupCommand extends Command
{
    protected $signature = 'db:backup {--keep=7}';

    protected $description = 'Backup database (mysqldump + gzip + retensi)';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');
        if ($keep < 1) {
            $this->warn('--keep="'.$this->option('keep').'" bukan angka ≥ 1, pakai bawaan 7.');
            $keep = 7;
        }

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");
        if (! $db || ($db['driver'] ?? '') !== 'mysql') {
            $this->error('db:backup hanya mendukung MySQL/MariaDB.');

            return self::FAILURE;
        }

        $dump = $this->findMysqldump();
        if ($dump === '') {
            $msg = 'mysqldump tidak ditemukan di server. Install mysql-client agar backup otomatis berjalan.';
            $this->error($msg);
            Log::error('[db:backup] '.$msg);
            $this->sendAlert('❌ <b>BACKUP DB GAGAL</b>'."\n".$msg);

            return self::FAILURE;
        }

        $dir = storage_path('backups');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $this->error('Tidak bisa membuat direktori '.$dir);

            return self::FAILURE;
        }

        $stamp = date('Ymd_His');
        $tmpSql = $dir.'/.db_'.$stamp.'.sql';
        $final = $dir.'/db_'.$stamp.'.sql.gz';

        $host = $db['host'] ?? 'localhost';
        $port = (int) ($db['port'] ?? 3306);
        $name = $db['database'] ?? '';
        $user = $db['username'] ?? '';
        $pass = (string) ($db['password'] ?? '');
        if ($name === '' || $user === '') {
            $this->error('Konfigurasi database belum lengkap.');

            return self::FAILURE;
        }

        // Kredensial lewat file defaults 0600, bukan "MYSQL_PWD='pass' mysqldump".
        $cnf = $dir.'/.my_'.$stamp.'.cnf';
        $passEsc = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', ''], $pass);
        @file_put_contents($cnf, "[client]\npassword=\"{$passEsc}\"\n");
        @chmod($cnf, 0600);

        $cmd = sprintf(
            '%s --defaults-extra-file=%s --single-transaction --quick --skip-lock-tables --routines --events -h %s -P %d -u %s %s --result-file=%s 2>&1',
            escapeshellarg($dump),
            escapeshellarg($cnf),
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($name),
            escapeshellarg($tmpSql)
        );

        $out = [];
        $code = 0;
        try {
            @exec($cmd, $out, $code);
            $ok = $code === 0 && is_file($tmpSql) && filesize($tmpSql) > 1024;
        } catch (\Throwable $e) {
            $ok = false;
            $out[] = $e->getMessage();
        } finally {
            @unlink($cnf);
        }

        if (empty($ok)) {
            @unlink($tmpSql);
            // Pesan mysqldump (stderr digabung ke stdout) bisa memuat host/nama
            // DB; kirim secukupnya ke log, jangan utuh-utuh ke channel notifikasi.
            $detail = trim(preg_replace('/\s+/', ' ', implode(' ', $out)) ?? '');
            $detail = mb_substr($detail, 0, 200);
            $msg = 'mysqldump gagal'.($detail !== '' ? ': '.$detail : '. Periksa kredensial DB.');
            $this->error($msg);
            Log::error('[db:backup] '.$msg.' (exit='.$code.')');
            $this->sendAlert('❌ <b>BACKUP DB GAGAL</b>'."\n".$msg."\nWaktu: ".date('d/m/Y H:i'));

            return self::FAILURE;
        }

        // Kompres dengan PHP (tanpa tergantung binary gzip)
        $fh = null;
        $gz = null;
        try {
            $gz = gzopen($final, 'wb9');
            $fh = fopen($tmpSql, 'rb');
            while (! feof($fh)) {
                gzwrite($gz, fread($fh, 1048576));
            }
        } catch (\Throwable $e) {
            $this->error('Kompresi gagal: '.$e->getMessage());
            // Jangan tinggal file setengah jadi + dump mentah di disk: retensi
            // hanya memindai db_*.sql.gz, dan .sql polos tidak akan pernah terhapus.
            if (is_resource($gz)) {
                gzclose($gz);
            }
            @unlink($final);
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
            @unlink($tmpSql);
        }

        // Retensi: simpan N terbaru
        $files = glob($dir.'/db_*.sql.gz') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }

        $size = round((filesize($final) ?: 0) / 1024 / 1024, 2);
        $this->info("OK: {$final} ({$size} MB)");
        Log::info("[db:backup] OK {$final} ({$size} MB)");

        return self::SUCCESS;
    }

    private function findMysqldump(): string
    {
        $candidates = ['mysqldump'];
        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/lampp/bin/mysqldump'] as $p) {
            if (is_file($p) && is_executable($p)) {
                return $p;
            }
        }
        foreach ($candidates as $c) {
            $out = [];
            @exec('command -v '.escapeshellarg($c).' 2>/dev/null', $out, $code);
            if ($code === 0 && ! empty($out[0])) {
                return trim($out[0]);
            }
        }

        return '';
    }

    private function sendAlert(string $msg): void
    {
        // Fallback WA bila TG mati: coba keduanya, jangan diam bila gagal.
        try {
            $notif = new NotificationService();
            $sent = $notif->sendTelegram($msg);
            try {
                $sent = $notif->sendWhatsApp($msg) || $sent;
            } catch (\Throwable $e2) {
            }
            if (! $sent) {
                Log::error('[db:backup] alert TG+WA gagal terkirim.');
            }
        } catch (\Throwable $e) {
            Log::error('[db:backup] alert gagal: '.$e->getMessage());
        }
    }
}

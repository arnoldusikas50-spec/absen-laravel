<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------------------------------------------------------
// Penjadwalan cron — porting CRONTAB.MD + file `cronjob` (CI4) ke Scheduler.
// Semua jam memakai WITA (Asia/Makassar), sama dengan appTimezone CI4.
// config/app.php masih 'UTC', jadi tiap jadwal diberi ->timezone() eksplisit.
// Syarat server: satu baris crontab `* * * * * php artisan schedule:run`
// menggantikan baris-baris `php spark ...` di crontab lama.
// ---------------------------------------------------------------------------

// CRONTAB #8 — Health check tiap 10 menit (diam saat sehat, alert TG/WA saat gagal).
Schedule::command('health:check')->everyTenMinutes()->timezone('Asia/Makassar');

// CRONTAB #9 — Backup database, tiap hari 02:00.
Schedule::command('db:backup')->dailyAt('02:00')->timezone('Asia/Makassar');

// Saran di docblock App\Commands\PruneLogs CI4 — rotasi/hapus log harian
// 03:30, setelah backup 02:00. (Tidak ada di crontab, hanya jadwal saran.)
Schedule::command('logs:prune')->dailyAt('03:30')->timezone('Asia/Makassar');

// ---------------------------------------------------------------------------
// TIDAK dijadwalkan (sengaja — lihat laporan Tahap 1C):
//  - dapodik:sync — auto-sync Dapodik NONAKTIF (CRONTAB.MD §5); aktifkan di
//    pengaturan Dapodik dulu, baru tambahkan jadwal bila dibutuhkan.
//  - sync:worker — daemon kontinu, jalankan sebagai proses latar / systemd,
//    bukan lewat scheduler.
//  - check:denda, cron:notif-log, db:setup_audit, absensi:single-setting,
//    teguran:audit, denda:sync-siswa, wa:bulk, tmp:f42upload — manual /
//    diagnostik / sekali jalan, tanpa jadwal cron.
//  - Job HTTP /cron/* (ucapan-pagi 06:00, ucapan-sore 15:00, daily 18:00,
//    rekap-piket 18:30, apel-kesadaran tgl 16 19:00, weekly-denda Sabtu
//    18:05, monthly-pdf tgl 1 07:10, semester-recap 1 Jul & 2 Jan 19:00,
//    poin-siswa 19:30) TIDAK punya padanan spark command di CI4 — logikanya
//    hanya ada di App\Controllers\CronController. Tetap dijalankan lewat
//    curl ke endpoint /cron/* seperti di CRONTAB.MD sampai controller itu
//    di-port (pekerjaan Tahap lain, di luar Tahap 1C).
//  - Baris CBT di file `cronjob` (cbt:proses_scoring_queue, cbt:auto_close_
//    expired, cbt:quality_maintenance, dapodik:sync 02:00 ujian) milik
//    aplikasi ujian.ikas.my.id, bukan aplikasi absensi ini.
// ---------------------------------------------------------------------------

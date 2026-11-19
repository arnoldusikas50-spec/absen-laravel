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

// Ucapan Selamat Ulang Tahun Guru — tiap hari 06:30 WITA
Schedule::command('cron:ulang-tahun')->dailyAt('06:30')->timezone('Asia/Makassar');

// ---------------------------------------------------------------------------
// Job /cron/* — dijalankan in-process lewat `cron:run` (tanpa curl, DNS, dan
// CRON_SECRET_KEY). Jam & parameter mengikuti blok crontab di CRONTAB.MD §4.
// ---------------------------------------------------------------------------

// CRONTAB #1 — Ucapan selamat pagi.
Schedule::command('cron:run ucapan-pagi')->dailyAt('07:00')->timezone('Asia/Makassar');

// CRONTAB #2 — Ucapan pulang (hari kerja saja).
Schedule::command('cron:run ucapan-sore')->dailyAt('15:00')->timezone('Asia/Makassar');

// CRONTAB #3 — Proses harian: ALPHA, surat teguran, lupa checkout.
Schedule::command('cron:run daily')->dailyAt('18:00')->timezone('Asia/Makassar');

// CRONTAB #3b — Rekap laporan piket harian.
Schedule::command('cron:run rekap-piket')->dailyAt('18:30')->timezone('Asia/Makassar');

// CRONTAB #4 — Rekap denda mingguan (Sabtu).
Schedule::command('cron:run weekly-denda')->weeklyOn(6, '18:05')->timezone('Asia/Makassar');

// CRONTAB #5 — PDF presensi + denda bulan lalu (tgl 1).
Schedule::command('cron:run monthly-pdf')->monthlyOn(1, '07:10')->timezone('Asia/Makassar');

// CRONTAB #6 — Rekap semester ganjil (1 Juli).
Schedule::command('cron:run semester-recap --semester=1 --tahun='.date('Y'))
    ->yearlyOn(7, 1, '19:00')->timezone('Asia/Makassar');

// CRONTAB #7 — Rekap semester genap (2 Januari, tahun ajaran sebelumnya).
Schedule::command('cron:run semester-recap --semester=2 --tahun='.(date('Y') - 1))
    ->yearlyOn(1, 2, '19:00')->timezone('Asia/Makassar');

// CRONTAB #8 — Hitung ulang poin siswa.
Schedule::command('cron:run poin-siswa')->dailyAt('19:30')->timezone('Asia/Makassar');

// CRONTAB #9 — Apel kesadaran (tgl 16).
Schedule::command('cron:run apel-kesadaran')->monthlyOn(16, '19:00')->timezone('Asia/Makassar');

// ---------------------------------------------------------------------------
// TIDAK dijadwalkan (sengaja — lihat laporan Tahap 1C):
//  - dapodik:sync — auto-sync Dapodik NONAKTIF (CRONTAB.MD §5); aktifkan di
//    pengaturan Dapodik dulu, baru tambahkan jadwal bila dibutuhkan.
//  - sync:worker — daemon kontinu, jalankan sebagai proses latar / systemd,
//    bukan lewat scheduler.
//  - check:denda, cron:notif-log, db:setup_audit, absensi:single-setting,
//    teguran:audit, denda:sync-siswa, wa:bulk, tmp:f42upload — manual /
//    diagnostik / sekali jalan, tanpa jadwal cron.
//  - mark-alpha dan ulang-tahun tetap tersedia lewat /cron/* (mark-alpha
//    sudah tercakup `daily`; ulang-tahun memakai `cron:ulang-tahun` 06:30).
//  - Baris CBT di file `cronjob` (cbt:proses_scoring_queue, cbt:auto_close_
//    expired, cbt:quality_maintenance, dapodik:sync 02:00 ujian) milik
//    aplikasi ujian.ikas.my.id, bukan aplikasi absensi ini.
// ---------------------------------------------------------------------------

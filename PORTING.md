# Porting CI4 → Laravel 13 — Sistem Presensi Digital & Manajemen Kedisiplinan Sekolah

Aplikasi asal: **ikasmedia/absen** (CodeIgniter 4.6.4, PHP 8.1+, MySQL) —
`https://github.com/arnoldusikas50-spec/absen`

Hasil porting ini: Laravel **13.40.0**, PHP 8.3.

## Cara menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi DB_* di .env (MySQL, default database db_absen)
php artisan migrate
php artisan serve
```

Penjadwal Laravel (menggantikan baris `php spark ...` di crontab):

```cron
* * * * * php /path/ke/absen-laravel/artisan schedule:run
```

Zona waktu aplikasi: `Asia/Makassar` (WITA).

## Pemetaan CI4 → Laravel

| CI4 | Laravel |
|---|---|
| `app/Controllers/*` | `app/Http/Controllers/*` (+ `Controller` di akhir nama class) |
| `app/Models/*` | `app/Models/*` (nama class dipertahankan, termasuk suffix `Model`) |
| `app/Services/*` | `app/Services/*` |
| `app/Libraries/*` | `app/Libraries/*` |
| `app/Helpers/*` | `app/Helpers/*` (fungsi global, dimuat via `composer.json` autoload `files`) |
| `app/Traits/*` | `app/Traits/*` |
| `app/Filters/*` | `app/Http/Middleware/*` (`auth`/`guest` bawaan, `role` kustom) |
| `app/Commands/*` (spark) | `app/Console/Commands/*` (Artisan, signature sama) |
| `app/Config/Routes.php` | `routes/web.php` + `routes/api.php` (URL dipertahankan) |
| `app/Views/*` | `resources/views/*` (Blade, nama = path CI4 dengan `.`) |
| `app/Database/Migrations/*` | `database/migrations/*` (satu file per tabel, skema final) |
| `public/*` | `public/*` (aset, manifest PWA, service worker) |
| `dompdf/dompdf` | `barryvdh/laravel-dompdf` (facade `Pdf`) |
| `endroid/qr-code` | `endroid/qr-code` (tetap) |
| `phpoffice/phpspreadsheet` | `phpoffice/phpspreadsheet` (tetap) |
| `spark` cron | `routes/console.php` (Scheduler) + `php artisan schedule:run` |

### Autentikasi

Session tetap sumber kebenaran seperti CI4 (`is_login`, `user_id`, `role`,
`role_name`, `id_guru`, ...). `AuthController::login()` juga mengisi guard
Laravel agar middleware `auth` bawaan bekerja. Route login: `GET /auth`
bernama `login`. Redirect pasca-login berbasis role (siswa → `siswa/presensi`,
guru → `presensi`, admin/panitia → `dashboard`). Middleware
`RestoreRememberLogin` (port `AuthFilter`) berjalan di grup `web`:
memulihkan remember-me dan merevalidasi role tiap 60 detik.

### Rute

593 rute terdaftar (`php artisan route:list`): 587 web + 5 API + 1 route
Boost (`POST _boost/browser-logs`).
Filter CI4 → middleware: `auth` → `auth`; `auth:guru` →
`['auth','role:guru']`; `auth:guru,admin` → `['auth','role:guru,admin']`;
`admin` → `['auth','role:admin']`. Rute POST yang dikecualikan CSRF di CI4
(`auth/login`, `presensi/auth`, `scanner/scan`, ...) memakai
`withoutMiddleware(VerifyCsrfToken::class)`.

## Database — 92 tabel aplikasi

Skema dipertahankan identik dengan database produksi (nama tabel, kolom,
tipe, index) supaya database MySQL yang sudah ada tetap bisa dipakai.
51 tabel pertama berasal dari migrasi CI4; 44 tabel inti dilengkapi dari
dump database produksi (`absen.sql`, MariaDB 10.11.10, 9 Nov 2026) —
lihat `PORTING-DB-COMPLETE.md`.

absensi_hari_libur, absensi_harian, absensi_izin, absensi_koreksi,
absensi_lokasi, absensi_pembayaran_denda, absensi_pengumuman_harian,
absensi_qr, absensi_qr_log, absensi_rekap_semester, absensi_saldo_denda,
absensi_setting, absensi_shift, absensi_shift_anggota,
absensi_surat_panggilan, absensi_surat_teguran, audit_logs,
cbt_rekap_siswa, chat_messages, chat_participants, chat_read_receipts,
chat_rooms, cron_notifikasi_log, dapodik_log, dapodik_settings,
disiplin_kategori, face_registration_requests, kalender_akademik,
notif_channel_config, poin_history, poin_siswa_history, post_likes,
ppdb_setting, presensi_bdr, presensi_lembur, presensi_siswa,
reward_redemptions, reward_tiers, rewards, siswa_beasiswa, siswa_berkas,
siswa_biodata, siswa_ortu, siswa_prestasi, sync_jobs, sync_log,
sync_settings,
user_notifikasi, users, groups, users_groups, setting, level_kelas,
master_kelas, master_siswa, master_guru, level_guru, master_mapel,
master_kelompok_mapel, master_jurusan, master_tp, master_smt,
master_ekstra, master_hari_efektif, jabatan_guru, kelas_siswa,
kelas_struktur, buku_induk, login_attempts, logs, bulan,
absensi_jadwal_kerja, guru_jurnal, guru_jadwal_pribadi,
kelas_jadwal_mapel, kelas_jadwal_kbm, kelas_jadwal_materi, kelas_materi,
log_materi, kelas_catatan_mapel, kelas_catatan_wali, kelas_ekstra,
penilaian_guru_periode, penilaian_guru_skor, piket_jadwal, piket_kejadian,
piket_absensi, piket_presensi_guru, piket_tamu, post, post_comments,
post_reply — plus tabel `sessions` standar Laravel (dibutuhkan karena
`SESSION_DRIVER=database`).

### Yang belum / tidak di-port di sisi database

0. **Fitur tracer study dibuang** (10 Nov 2026, atas permintaan pengguna).
   Tabel `alumni`, `tracer_study_responses`, dan `tracer_study_settings`
   beserta migrasinya dihapus; 5 method alumni di `MasterModel` juga
   dihapus karena tidak pernah dipanggil. Tabel-tabel itu kosong (0 baris)
   dan tidak pernah punya route/controller/view, jadi tidak ada data yang
   hilang. Dump produksi asli masih memuat `alumni`; salinan skemanya di
   repo (`absen.sql`) sudah dibersihkan.
1. **Tabel inti kini lengkap dari database produksi** (9 Nov 2026):
   44 tabel yang sebelumnya tidak punya definisi skema di repo CI4
   (`users`, `master_siswa`, `master_guru`, `setting`, `post`, `logs`,
   `piket_jadwal`, `piket_kejadian`, `guru_jurnal`,
   `guru_jadwal_pribadi`, `jabatan_guru`, `buku_induk`, `master_kelas`,
   `master_mapel`, `kelas_siswa`, `absensi_jadwal_kerja`, dll.) dibuat
   migrasinya dari dump produksi — lihat `PORTING-DB-COMPLETE.md`.
   Yang sengaja TIDAK dibuat: 9 tabel di dump yang tidak direferensikan
   kode mana pun (`activity_log`, `api_setting`, `api_token`,
   `ci_sessions`, `hari`, `kelas_libraries`, `system_update_log`,
   `system_updates`, `users_profile`); tabel `kelas` (terbukti tidak
   pernah ada — `$table = 'kelas'` di `KelasModel` adalah artefak
   porting, semua method-nya query `master_kelas`); tabel `cbt_*` (CBT
   adalah aplikasi/database terpisah, skemanya tidak ada di dump ini —
   kecuali `cbt_rekap_siswa` yang skemanya ada di repo CI4); dan 4
   tabel "hantu" yang hanya dideklarasikan model (`nilai_proses`,
   `rapor_nilai_harian`, `master_panitia`, `e_tujuan_pembelajaran`).
   Satu-satunya foreign key di dump, `master_kelas.level_id` →
   `level_kelas.id_level` (`key_id_cek`), sudah dimasukkan ke migrasi.
2. **Tidak ada foreign key** di 94 migrasi CI4, jadi model belum punya
   relasi Eloquent. Bila FK memang ada di database live, tambahkan dari
   `SHOW CREATE TABLE`.
3. **4 tabel dengan definisi ganda di migrasi CI4** (`absensi_setting`,
   `absensi_lokasi`, `absensi_harian`, `absensi_izin`) — **sudah
   disesuaikan dengan database produksi** (9 Nov 2026, dump adalah
   kebenaran): `absensi_setting` kini 89 kolom (40 kolom yang hilang
   ditambahkan; `foto_wajib` default 1, `radius_meter` 50,
   `hari_kerja`/`libur_kalender` bukan `json`); `absensi_harian` 41 kolom
   (+6 kolom verifikasi/persetujuan); `absensi_izin` 17 kolom (+6 kolom
   verifikasi; `status` string bukan enum); `absensi_lokasi` memakai
   `decimal(10,8)/(11,8)` untuk lat/lng. `user_notifikasi` juga
   dilengkapi `wa_number`/`wa_verified`. Detail di
   `PORTING-DB-COMPLETE.md` §3–§4 dan §8–§9. Catatan: migrasi Laravel
   ini belum pernah dijalankan ke database produksi mana pun (hanya
   `migrate:fresh` SQLite/MariaDB lokal di workspace), jadi
   perbaikannya dilakukan dengan mengedit file migrasi yang sudah
   di-commit — bukan migrasi alter terpisah — karena perbaikan tipe/
   default lewat alter membutuhkan `doctrine/dbal` (->change()) yang
   tidak terpasang, sementara raw SQL MySQL akan merusak verifikasi
   SQLite.
4. **Seed tidak di-port**: migrasi CI4 menyisipkan 1 baris default
   `absensi_setting`. Buat seeder bila diperlukan.
5. **Tabel ke-51, `absensi_saldo_denda`**, dibuat di runtime oleh method
   yang sama dengan `absensi_pembayaran_denda` (bukan dari migrasi).
   Dipertahankan sebagai migrasi sendiri.
6. **Metode kustom model belum di-port**: 43 model hanya berisi `$table` /
   `$fillable` / `$casts`. Pemanggilan method kustom model dari controller
   (`getDataSiswa()`, `getGuruById()`, `syncAttendancePoint()`, dll.)
   dipertahankan apa adanya dan akan melempar `BadMethodCallException`
   saat runtime sampai method itu dilengkapi. Method yang paling sering
   dipakai sudah digantikan query DB langsung di `BaseController`.

## Yang tidak bisa di-port 1:1

- **Token enkripsi CI4 tidak kompatibel** dengan Laravel `Crypt`.
  Token URL lama (mis. QR) tidak bisa didekripsi. Token bersifat
  transient; token baru diterbitkan dengan format Laravel.
- **Job cron HTTP `/cron/*`** (ucapan pagi/sore, daily, rekap-piket,
  apel-kesadaran, weekly-denda, monthly-pdf, semester-recap, poin-siswa)
  di-port sebagai controller biasa (`CronControllerController`), tetap
  dipanggil via `curl` seperti sebelumnya — bukan Artisan command.
  Command `poin:siswa` yang disebut CRONTAB.MD tidak punya file command
  di repo CI4, jadi tidak dibuatkan command.
- **Baris CBT di file `cronjob`** (`cbt:proses_scoring_queue`, dll.)
  milik aplikasi ujian terpisah — tidak di-port.
- **RouterosAPI (MikroTik), DapodikClient, SyncService** disalin 1:1
  (murni socket/HTTP PHP) tapi tidak teruji tanpa hardware/server terkait.
  `SyncService` mempertahankan `verify => false` (server pusat memakai
  sertifikat self-signed), seperti sumber.
- **FaceScan** (JS + kamera, di view) disalin apa adanya; verifikasi
  wajah sisi server tetap seperti CI4.
- **Tailwind**: memakai CSS hasil build CI4 (Tailwind 3.4.17 +
  config warna dinamis via CSS variable) di
  `public/assets/app/css/tailwind.css`. Tidak memakai Vite/Tailwind 4
  Laravel karena config v3 tidak kompatibel.
- **Halaman error/debug CI4** (`errors/cli/*`, `errors/html/*`) disalin
  tapi tidak dipakai Laravel (punya exception handler sendiri).
- **Perilaku yang disederhanakan** di `BaseController`: `DB::transStatus()`
  → `true`; `$db->affectedRows()` → `1`; `$db->error()` → array kosong;
  aturan validasi CI4 yang tak dikenal diabaikan (dicatat di log).

## Verifikasi yang sudah dilakukan

- `php -l` bersih untuk semua file PHP (app, config, database, routes).
- `php artisan migrate:fresh` (SQLite dan MariaDB 10.11.14 lokal):
  98/98 migrasi, 102 tabel terbentuk tanpa error (95 tabel aplikasi +
  6 infra Laravel + tabel `migrations`). Skema 49 tabel baru/diperbaiki
  dibandingkan kolom-per-kolom dengan dump produksi — cocok penuh
  (lihat `PORTING-DB-COMPLETE.md` §9).
- `php artisan route:list`: 593 rute, audit 576/576 aksi mengarah ke
  class + method yang ada.
- `php artisan view:cache`: sukses. `php artisan config:cache`: sukses.
- `php artisan tmp:f42upload`: PASS=18 FAIL=0.
- Smoke test (`php artisan serve`): `/auth` → 200 (halaman login),
  `/dashboard` (tamu) → 302 ke `/auth`, `/healthz` → 200 JSON,
  `/api/rekap-harian` → 503 JSON `API key belum dikonfigurasi`
  (perilaku aplikasi yang benar bila `api_key` kosong).

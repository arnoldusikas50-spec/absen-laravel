# Absen Sekolah

Aplikasi presensi digital dan manajemen kedisiplinan sekolah berbasis Laravel.

## Fitur Utama

- Presensi guru: masuk dan pulang melalui Face ID, QR acara, RFID/NFC, atau input manual.
- Presensi siswa: kiosk RFID/NFC, pemindai QR (kamera atau scanner gun), NIS, dan input per rombel.
- Jurnal mengajar guru per sesi/jam pelajaran beserta validasi guru piket atau kepala sekolah.
- Monitoring dan rekap absensi, keterlambatan, alpha, jurnal, piket, lembur, dan izin.
- Manajemen data master sekolah, guru, siswa, kelas, jadwal, tahun ajaran, dan semester.
- Pengaturan notifikasi WhatsApp/Telegram, integrasi perangkat, dan sinkronisasi data.

## Peran

| Peran | Tugas utama |
| --- | --- |
| Admin / Tata Usaha | Data master, pengaturan, monitoring, persetujuan, dan rekap |
| Guru | Presensi, jurnal, jadwal, presensi siswa, piket, lembur, dan izin |
| Guru Piket | Validasi jurnal dan pemantauan piket harian |
| Kepala Sekolah | Persetujuan dan pengawasan akademik |
| Siswa | Presensi melalui kiosk atau QR |

Zona waktu aplikasi: `Asia/Makassar` (WITA).

## Persyaratan

- PHP 8.3 atau newer
- Composer 2
- MySQL/MariaDB
- Node.js 20 atau newer untuk build aset frontend

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Isi variabel database di `.env`, lalu jalankan:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

## Penjadwalan

Tambahkan satu baris crontab berikut agar scheduler Laravel berjalan:

```cron
* * * * * php /path/ke/absen-laravel/artisan schedule:run
```

Semua jadwal cron didefinisikan di `routes/console.php` dengan timezone `Asia/Makassar`.

## Dokumentasi

Panduan operasional untuk admin, guru, piket, kepala sekolah, dan siswa ada di `docs/PANDUAN-PENGGUNAAN.md`.

## Struktur Singkat

- `app/Http/Controllers` — controller web dan API
- `app/Models` — model Eloquent
- `app/Services` — logika bisnis dan integrasi
- `app/Console/Commands` — perintah Artisan
- `resources/views` — tampilan Blade
- `routes` — rute web, API, dan scheduler
- `database/migrations` — skema database

## License

Proprietary.

# Melengkapi Migrasi dari Database Produksi — Sistem Presensi Digital & Manajemen Kedisiplinan Sekolah

Aplikasi asal: **ikasmedia/absen** (CodeIgniter 4.6.4). Target: `~/workspace/absen-laravel`
(Laravel 13.40.0, PHP 8.3). Sumber skema: dump produksi
`~/workspace/user/files/absen.sql` (phpMyAdmin, MariaDB 10.11.10, database
`absen-ikas`, dibuat 9 Nov 2026 21:53, 91 CREATE TABLE).

Dokumen ini dibuat dari inventarisasi dump vs migrasi Laravel, lalu dilengkapi
dengan daftar migrasi yang dibuat/diperbaiki dan hasil verifikasi.

**Aturan data:** dump berisi data produksi (baris INSERT, hash password, data
pribadi). Dokumen dan migrasi ini hanya memakai pernyataan CREATE TABLE
(struktur). Tidak ada data baris, hash, atau data pribadi yang disalin ke mana
pun. File dump tetap di `~/workspace/user/files/absen.sql` dan tidak diunggah.

## 1. Inventarisasi — diff dump vs migrasi

Dihitung dari nama file CREATE TABLE di dump vs `Schema::create()` di 54 file
migrasi Laravel:

| | Jumlah |
|---|---|
| Tabel di dump | **91** |
| Tabel di migrasi Laravel (nama `Schema::create` berbeda) | **54** (51 file: file cache membuat 2 tabel, file jobs membuat 3) |
| Di dump DAN di migrasi | **37** |
| Hanya di dump | **54** |
| Hanya di migrasi | **17** |
| Cek konsistensi | 37 + 54 = 91 ✔; 37 + 17 = 54 ✔ |

Koreksi atas `PORTING.md`: tertulis "Database — 52 tabel aplikasi". Migrasi
Laravel sebenarnya memuat **48 tabel aplikasi + 6 infra = 54 tabel**. Daftar
"±12 tabel inti" tanpa skema di `PORTING.md` juga tidak lengkap — kenyataannya
54 tabel dump belum punya migrasi.

Catatan sumber skema: ekstraksi CREATE TABLE hanya berisi daftar kolom —
PRIMARY KEY, KEY/INDEX, dan AUTO_INCREMENT diambil dari bagian `ALTER TABLE`
dump (177 pernyataan `ALTER TABLE`, 91 `ADD PRIMARY KEY`, 121 baris `KEY`).
Nilai `AUTO_INCREMENT=n` tidak dicantumkan (itu membocorkan jumlah baris).

### 1.1 Tabel hanya di dump (54) — verdict referensi kode

"Ref" = jumlah hit konteks-tabel (`DB::table('x')`, `->table('x')`,
`join('x',…)`, `Schema::hasTable('x')`, `tableExists('x')`, `from('x')`,
`$table = 'x'`) di Laravel / CI4. Hitungan string mentah saja menyesatkan
untuk nama generik (`hari`, `bulan`, `setting`, `users`, `post`, `logs`)
karena sebagian besar hit adalah nama kolom/variabel, bukan tabel.

| Tabel | Ref Laravel / CI4 | Verdict |
|---|---|---|
| absensi_jadwal_kerja | 58 / 63 | DIREERENSIKAN |
| activity_log | 0 / 0 | TIDAK DIREERENSIKAN |
| alumni | 0 / 0 | TIDAK DIREERENSIKAN (dihapus 10 Nov 2026 — tracer study) |
| api_setting | 0 / 0 | TIDAK DIREERENSIKAN |
| api_token | 0 / 0 | TIDAK DIREERENSIKAN |
| buku_induk | 48 / 50 | DIREERENSIKAN |
| bulan | 2 / 2 | DIREERENSIKAN |
| ci_sessions | 0 / 0 | TIDAK DIREERENSIKAN |
| groups | 2 / 3 | DIREERENSIKAN |
| guru_jadwal_pribadi | 1 / 1 | DIREERENSIKAN |
| guru_jurnal | 11 / 11 | DIREERENSIKAN |
| hari | 0 / 0 | TIDAK DIREERENSIKAN (sebagai tabel) |
| jabatan_guru | 31 / 36 | DIREERENSIKAN |
| kelas_catatan_mapel | 2 / 2 | DIREERENSIKAN |
| kelas_catatan_wali | 2 / 2 | DIREERENSIKAN |
| kelas_ekstra | 1 / 2 | DIREERENSIKAN |
| kelas_jadwal_kbm | 5 / 5 | DIREERENSIKAN |
| kelas_jadwal_mapel | 28 / 28 | DIREERENSIKAN |
| kelas_jadwal_materi | 0† / 0† | DIREERENSIKAN † (peta dinamis) |
| kelas_libraries | 0 / 0 | TIDAK DIREERENSIKAN |
| kelas_materi | 0† / 0† | DIREERENSIKAN † (peta dinamis) |
| kelas_siswa | 41 / 42 | DIREERENSIKAN |
| kelas_struktur | 1 / 1 | DIREERENSIKAN |
| level_guru | 7 / 7 | DIREERENSIKAN |
| level_kelas | 4 / 4 | DIREERENSIKAN |
| log_materi | 1 / 1 | DIREERENSIKAN |
| login_attempts | 11 / 3 | DIREERENSIKAN |
| logs | 6 / 3 | DIREERENSIKAN |
| master_ekstra | 4 / 4 | DIREERENSIKAN |
| master_guru | 176 / 181 | DIREERENSIKAN |
| master_hari_efektif | 2 / 2 | DIREERENSIKAN |
| master_jurusan | 24 / 25 | DIREERENSIKAN |
| master_kelas | 86 / 89 | DIREERENSIKAN |
| master_kelompok_mapel | 16 / 16 | DIREERENSIKAN |
| master_mapel | 42 / 42 | DIREERENSIKAN |
| master_siswa | 64 / 67 | DIREERENSIKAN |
| master_smt | 150 / 13 | DIREERENSIKAN |
| master_tp | 161 / 21 | DIREERENSIKAN |
| penilaian_guru_periode | 7 / 5 | DIREERENSIKAN |
| penilaian_guru_skor | 7 / 6 | DIREERENSIKAN |
| piket_absensi | 21 / 22 | DIREERENSIKAN |
| piket_jadwal | 17 / 16 | DIREERENSIKAN |
| piket_kejadian | 8 / 7 | DIREERENSIKAN |
| piket_presensi_guru | 9 / 10 | DIREERENSIKAN |
| piket_tamu | 9 / 8 | DIREERENSIKAN |
| post | 1 / 1 | DIREERENSIKAN |
| post_comments | 1 / 1 | DIREERENSIKAN |
| post_reply | 1 / 1 | DIREERENSIKAN |
| setting | 30 / 30 | DIREERENSIKAN |
| system_update_log | 0 / 0 | TIDAK DIREERENSIKAN |
| system_updates | 0 / 0 | TIDAK DIREERENSIKAN |
| users | 112 / 102 | DIREERENSIKAN |
| users_groups | 21 / 19 | DIREERENSIKAN |
| users_profile | 0 / 0 | TIDAK DIREERENSIKAN |

**Rekap: 44 DIREERENSIKAN, 9 TIDAK DIREERENSIKAN** (`activity_log`,
`api_setting`, `api_token`, `ci_sessions`, `hari`, `kelas_libraries`,
`system_update_log`, `system_updates`, `users_profile`).

Contoh referensi (maks. 3 per tabel; L = Laravel `app`, C = CI4 `app`):
- `absensi_jadwal_kerja`: L `SettingsController.php:48`
  `$db->table('absensi_jadwal_kerja')->orderBy('hari','ASC')`; L
  `PresensiSiswaController.php:332-333`; C `Services/RekapPdfService.php:241-242`.
- `buku_induk`: L `Services/DapodikSyncService.php:688-691`
  `Schema::hasTable('buku_induk')`, `DB::table('buku_induk')`; C
  `Models/SiswaPresensiModel.php:40`.
- `bulan`: L `Models/ElearningModel.php:28`, `Models/DropdownModel.php:28`
  (`getBulan()`); C sama di `:11`.
- `groups`: L `Models/UsersModel.php:61,81` (join `users_groups`); L
  `Helpers/init_helper.php:36`; C `Filters/AuthFilter.php:58`.
- `guru_jadwal_pribadi`: L `Models/GuruJadwalPribadiModel.php:18`
  `$table = 'guru_jadwal_pribadi'`; C sama.
- `guru_jurnal`: L `Models/JurnalGuruModel.php:18` `$table = 'guru_jurnal'`;
  L `Models/PenilaianGuruModel.php:376`; L `DashboardController.php:148`.
- `hari` (tidak direferensikan sebagai tabel): 64 hit string `'hari'` semuanya
  nama kolom atau kata dalam komentar; tidak ada `getHari()` berbasis tabel.
  Tabel dump-nya hanya 2 kolom (`id_hri`, `nama_hri`).
- `jabatan_guru`: L `Helpers/init_helper.php:272-273,281`; C sama.
- `kelas_catatan_mapel`: L `Models/KelasModel.php:28,53`; C `:11,35`.
- `kelas_catatan_wali`: L `Models/KelasModel.php:116,139`; C `:86,107`.
- `kelas_ekstra`: L `Models/KelasModel.php:476`; C `Models/RaporModel.php:214`,
  `Models/KelasModel.php:405`.
- `kelas_jadwal_kbm`: L `Models/ElearningModel.php:238,512,534`; C
  `Models/KelasModel.php:56`, `Models/ElearningModel.php:187,423`.
- `kelas_jadwal_mapel`: L `Http/Controllers/DataguruController.php:1380`; L
  `Models/ElearningModel.php:494,550`; C `Models/ElearningModel.php:410,443,453`.
- `kelas_jadwal_materi` †: hanya lewat peta tabel→kolom dinamis
  `REF_MAPEL` (L `DatamapelController.php:29`, C `Datamapel.php:26`) yang
  dipakai memeriksa referensi sebelum hapus master mapel.
- `kelas_materi` †: L `DatamapelController.php:30` (peta `REF_MAPEL`); L
  `Models/MasterModel.php:614` (peta hapus guru); L
  `DataguruController.php:788` (hitung dinamis); C sama.
- `kelas_siswa`: L `Libraries/SyncService.php:490,497`; L
  `Services/DapodikSyncService.php:717`; C
  `Services/SuratPanggilanService.php:384,532`.
- `kelas_struktur`: L `Models/KelasModel.php:149`; C `:116`.
- `level_guru`: L `Models/UsersModel.php:72`; L `Models/KelasModel.php:344`; L
  `Helpers/init_helper.php:281`; C `Models/UsersModel.php:59`,
  `Models/DropdownModel.php:119`.
- `level_kelas`: L `Services/DapodikSyncService.php:1278-1280`; L
  `DatakelasController.php:273`; C `Services/DapodikSyncService.php:1280-1282`.
- `log_materi`: L `Models/KelasModel.php:213`; C `:176`; juga di peta
  `REF_MAPEL` (`DatamapelController.php:33`).
- `login_attempts`: L `Models/AuthModel.php:326,339,358`; C `:316,329,347`.
- `logs`: L `Models/LogModel.php:17` `$table = 'logs'`; L `:38-39`
  `_ensureTableExists()`; C `Models/LogModel.php:13,41`.
- `master_ekstra`: L `DataguruController.php:1259-1260`; L
  `Models/DropdownModel.php:163`; C `Models/DropdownModel.php:131,143`.
- `master_guru`: L `Helpers/init_helper.php:252,260,281`; L
  `Models/DropdownModel.php` (`$table = 'master_guru'`); C
  `Models/UsersModel.php:97,118`.
- `master_hari_efektif`: L `Models/MasterModel.php:1097`; L
  `DatatahunController.php:240`; C `Models/MasterModel.php:931`.
- `master_jurusan`: L `Services/DapodikSyncService.php:1309-1320`; C sama.
- `master_kelas`: L `Services/PresensiBdrService.php:180,183,207`; L
  `Models/KelasModel.php:293,357,448`; C
  `Services/SuratPanggilanService.php:384,548`.
- `master_kelompok_mapel`: L `Models/DropdownModel.php:52`; L
  `Models/MasterModel.php:799,815`; C `Models/MasterModel.php:697,711,725`.
- `master_mapel`: L `Models/ElearningModel.php` (`$table = 'master_mapel'`); L
  `Services/DapodikSyncService.php:96-97,1071`; C sama.
- `master_siswa`: L `Models/MasterModel.php` (`$table = 'master_siswa'`); L
  `DatasiswaController.php:876,1210,1310`; C
  `Services/SuratPanggilanService.php:193-194,212`.
- `master_smt`: L `Services/DapodikSyncService.php:1414-1415`; L
  `SettingsController.php:66`; C `Services/WajibJurnalPulangService.php:129,162`.
  (150 hit Laravel vs 13 CI4 — port Laravel mengulang lookup semester aktif
  per method; CI4 memakai helper bersama.)
- `master_tp`: L `Services/DapodikSyncService.php:1407-1408`; L
  `Services/SiswaDendaService.php:593`; C
  `Services/WajibJurnalPulangService.php:128,161`. (161 vs 21, alasan sama.)
- `penilaian_guru_periode`: L `Models/PenilaianGuruModel.php:20`
  `$table = 'penilaian_guru_periode'`; L `:30-31` `_ensureTableExists`; C
  `Models/PenilaianGuruModel.php:21,74,85`.
- `penilaian_guru_skor`: L `Models/PenilaianGuruModel.php:47-48`
  (`Schema::create` bila belum ada); L `:105`; C `:40,93,111`.
- `piket_absensi`: L `Guru/PiketController.php:908`; L
  `Models/PiketModel.php:113-114` (`Schema::create` bila belum ada); C
  `Controllers/Guru/Piket.php:906`.
- `piket_jadwal`: L `Models/PiketJadwalModel.php:18` `$table = 'piket_jadwal'`;
  L `PresensiSiswaController.php:166-167`; C `Controllers/PresensiSiswa.php:162-163`.
- `piket_kejadian`: L `Models/PiketModel.php:24` `$table = 'piket_kejadian'`;
  L `DashboardController.php:170-171`; C `Models/PiketModel.php:9,71,1433`.
- `piket_presensi_guru`: L `Models/PiketModel.php:133-134,211`; C `:127`.
- `piket_tamu`: L `DashboardController.php:167-168`; L
  `Models/PiketModel.php:69`; C `Models/PiketModel.php:46,1354,1359`.
- `post`: L `Models/PostModel.php:16` `$table = 'post'`; C `:9`; juga di peta
  hapus guru (`MasterModel.php:614`) dan hitung tertaut
  (`DataguruController.php:791`).
- `post_comments`: L `Models/PostModel.php:87`; C `:58`.
- `post_reply`: L `Models/PostModel.php:97`; C `:66`.
- `setting`: L `Models/DashboardModel.php`, `Models/SettingsModel.php`
  (`$table = 'setting'`); L `Helpers/init_helper.php:58,72`; L
  `Libraries/Theme.php:79`; C `Services/SuratTeguranService.php:129-130`.
- `users`: L `Models/AuthModel.php`, `Models/UsersModel.php`
  (`$table = 'users'`); L `Helpers/init_helper.php:240-241`; L
  `Libraries/RememberLogin.php:48`; C `Models/UsersModel.php:49,66,113`.
- `users_groups`: L `Models/UsersModel.php:58,78`; L
  `Http/Controllers/BaseController.php:748,777`; L `Models/AuthModel.php:182`;
  C `Filters/AuthFilter.php:58`, `Models/UsersModel.php:47,64`.

Catatan: beberapa model Laravel membuat tabel secara runtime bila belum ada
(`Schema::create` di `LogModel`, `PenilaianGuruModel`, `PiketModel`) dengan
skema yang belum tentu sama dengan dump — migrasi dari dump tetap diperlukan
agar skema kanonik. Komentar `TODO: tabel … belum ada migrasinya` tersebar di
kode Laravel, mis. `Models/MasterModel.php:610` dan
`Models/ElearningModel.php:26`, `Models/KelasModel.php:62,266,288,316,353`.
Semua tabel yang disebut di TODO itu ada di dump.

### 1.2 Tabel hanya di migrasi (17)

- **6 infra Laravel:** `cache`, `cache_locks`, `jobs`, `job_batches`,
  `failed_jobs`, `sessions`.
- **11 tabel aplikasi:** `cbt_rekap_siswa`, `chat_messages`,
  `chat_participants`, `chat_read_receipts`, `chat_rooms`, `ppdb_setting`,
  `siswa_beasiswa`, `siswa_berkas`, `siswa_biodata`, `siswa_ortu`,
  `siswa_prestasi` — semuanya didefinisikan oleh migrasi CI4 (sebagian besar
  tertanggal 2026-01-17), tidak ada satu pun di dump produksi, dan tidak ada
  satu pun yang dirujuk kode aplikasi (controller/model/service/library/helper) di
  kedua repo. Penjelasan paling masuk akal: migrasi-migrasi 17 Jan 2026 itu
  tidak pernah dijalankan di database produksi (atau tabelnya kemudian
  dihapus), sementara porting Laravel menyalin skema final dari repo CI4 apa
  adanya. Keputusan mempertahankan/menghapus migrasi Laravel-nya adalah
  keputusan produk, bukan kebutuhan migrasi dari dump.

**Catatan penghapusan 2026-11-10:** dua tabel aplikasi lain yang semula ada
di daftar ini — `tracer_study_responses` dan `tracer_study_settings` —
dihapus beserta migrasinya (fitur tracer study tidak pernah dipakai, tabelnya
kosong). Migrasi `2026_11_09_000017_create_alumni_table.php` juga dihapus;
sehingga tabel `alumni` kini hanya ada di dump produksi, tidak di port lagi
(lihat §1.1).

### 1.3 Empat tabel "hantu" lain

Model Laravel mendeklarasikan `$table` untuk tabel yang tidak ada di dump
**maupun** di migrasi: `NilaiModel → nilai_proses`, `RaporModel →
rapor_nilai_harian`, `PanitiaModel → master_panitia`, `TujuanPembelajaranModel
→ e_tujuan_pembelajaran`. (Plus `CbtModel → cbt_jadwal`,
`CbtReportModel/CbtSiswaModel → cbt_siswa`, lihat §4.) Tabel-tabel ini tidak
bisa dibuat dari dump ini karena skemanya tidak ada di sini.

**Dikonfirmasi pengguna 2026-11-09: keempat tabel ini memang tidak dipakai.**
Tidak dibuat migrasinya. Model-modelnya tetap ada di kode; tiga di antaranya
tidak direferensikan controller mana pun (NilaiModel, RaporModel,
TujuanPembelajaranModel), dan PanitiaModel hanya dipakai
UserpanitiaController — controller itu sendiri tidak punya route, jadi
halaman panitia tidak bisa dibuka. Method yang menyentuh `master_panitia` di
DropdownModel/UsersModel hanya akan error bila dipanggil.

**Dihapus 2026-11-09 (atas persetujuan pengguna):** keempat file model
(`NilaiModel`, `RaporModel`, `PanitiaModel`, `TujuanPembelajaranModel`) dan
`UserpanitiaController` — tidak ada referensi tersisa ke class-class itu.
Catatan: `DashboardModel::getDataPanitiaByUserId()` masih query
`master_panitia`, dipanggil dari `UsersiswaController` hanya bila pengguna
berperan panitia — halaman `/usersiswa` akan error untuk pengguna panitia
bila tabelnya tetap tidak ada (saat ini tidak ada pengguna berperan panitia,
jadi jalur ini tidak pernah jalan).

## 2. Skema 44 tabel "hanya di dump" yang direferensikan kode

Format per kolom: `` `kolom` tipe [NOT NULL | NULL] [DEFAULT x] [keterangan] ``.
PK/index/AUTO_INCREMENT (AI) diambil dari `ALTER TABLE` dump. Tabel yang TIDAK
direferensikan (9 tabel §1.1) tidak dicantumkan di sini — skemanya tetap ada di
dump bila kelak dibutuhkan.

### 2.1 `absensi_jadwal_kerja` (5 kolom)
`id` int(10) UNSIGNED NOT NULL · `hari` int(11) NOT NULL · `jam_masuk` time NOT NULL DEFAULT '07:30:00' · `jam_pulang` time NOT NULL DEFAULT '15:00:00' · `is_libur` tinyint(1) NOT NULL DEFAULT 0. PK (`id`), `id` AI.

### 2.2 `buku_induk` (7 kolom)
`id_siswa` int(11) NOT NULL · `uid` varchar(255) NULL · `status` int(11) DEFAULT 1 · `tahun_masuk` varchar(30) NULL · `tahun_lulus` varchar(30) NULL · `kelas_awal` varchar(30) NULL · `kelas_akhir` varchar(30) NULL. PK (`id_siswa`), KEY `idx_status` (`status`). **Tanpa AI** pada `id_siswa`.

### 2.3 `bulan` (2 kolom)
`id_bln` int(11) NOT NULL · `nama_bln` varchar(25) NOT NULL. PK (`id_bln`), AI.

### 2.4 `groups` (3 kolom)
`id` mediumint(8) UNSIGNED NOT NULL · `name` varchar(20) NOT NULL · `description` varchar(100) NOT NULL. PK (`id`), AI.

### 2.5 `guru_jadwal_pribadi` (12 kolom)
`id` int(11) UNSIGNED NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_guru` int(11) NOT NULL · `hari` tinyint(1) NOT NULL · `jam_mulai` time NOT NULL · `jam_selesai` time NOT NULL · `id_mapel` int(11) NOT NULL · `id_kelas` int(11) NOT NULL · `ruangan` varchar(50) NULL · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(). PK (`id`), AI; KEY `id_guru_id_tp_id_smt_hari` (`id_guru`,`id_tp`,`id_smt`,`hari`), KEY `id_kelas_id_tp_id_smt_hari` (`id_kelas`,`id_tp`,`id_smt`,`hari`).

### 2.6 `guru_jurnal` (27 kolom)
`id_jurnal` bigint(20) UNSIGNED NOT NULL · `id_guru` int(11) NOT NULL · `id_jadwal` int(11) NULL · `id_kelas` int(11) NOT NULL · `id_mapel` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `tanggal` date NOT NULL · `jam_ke` varchar(20) NULL · `materi_pokok` text NULL · `tujuan_pembelajaran` text NULL · `model_belajar` varchar(100) NULL · `kegiatan_belajar` text NULL · `profil_pancasila` text NULL COMMENT 'JSON' · `jenis_asesmen` varchar(50) NULL · `refleksi` text NULL · `media_alat` text NULL · `siswa_absent` text NULL COMMENT 'JSON array of student IDs' · `tindak_lanjut` text NULL · `catatan_khusus` text NULL · `validasi_status` varchar(20) DEFAULT 'PENDING' · `validasi_oleh` int(11) NULL · `validasi_pada` datetime NULL · `validasi_catatan` varchar(500) NULL · `dokumentasi` varchar(255) NULL · `created_at` datetime NULL · `updated_at` datetime NULL. PK (`id_jurnal`), AI; KEY `id_guru`, KEY `id_kelas`, KEY `tanggal`.

### 2.7 `jabatan_guru` (8 kolom)
`id_jabatan_guru` varchar(50) NOT NULL · `id_guru` int(11) NULL · `id_jabatan` int(11) NOT NULL · `id_kelas` int(11) DEFAULT 0 · `mapel_kelas` longtext NULL · `ekstra_kelas` longtext NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL. PK (`id_jabatan_guru`), UNIQUE `uniq_jabatan_guru_periode` (`id_guru`,`id_tp`,`id_smt`). **PK varchar, tanpa AI.**

### 2.8 `kelas_catatan_mapel` (14 kolom)
`id_catatan` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `type` int(11) NOT NULL · `id_siswa` int(11) NULL · `id_mapel` int(11) NULL · `id_kelas` int(11) NULL · `id_guru` int(11) NULL · `level` varchar(1) NOT NULL DEFAULT '0' · `tgl` datetime NOT NULL DEFAULT current_timestamp() · `text` mediumtext NOT NULL · `readed` varchar(22) NOT NULL DEFAULT '0' · `reading` longtext NULL COMMENT 'array id_siswa yang membaca' · `jml` int(11) NOT NULL DEFAULT 0. PK (`id_catatan`), AI.

### 2.9 `kelas_catatan_wali` (12 kolom)
`id_catatan` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `type` int(11) NOT NULL COMMENT '1=semua siswa, 2=per siswa' · `level` varchar(1) NOT NULL COMMENT '1=saran, 2=teguran, 3=peringatan, 4=sangsi' · `tgl` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() · `id_siswa` int(11) NULL · `id_kelas` int(11) NULL · `text` mediumtext NOT NULL · `readed` varchar(22) NOT NULL DEFAULT '0' · `reading` longtext NULL · `jml` int(11) NOT NULL DEFAULT 0. PK (`id_catatan`), AI.

### 2.10 `kelas_ekstra` (5 kolom)
`id_kelas_ekstra` varchar(50) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_kelas` int(11) NULL · `ekstra` longtext NOT NULL. PK (`id_kelas_ekstra`). **PK varchar, tanpa AI.**

### 2.11 `kelas_jadwal_kbm` (11 kolom)
`id_kbm` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_kelas` int(11) NULL · `kbm_jam_pel` int(11) NOT NULL · `kbm_jam_mulai` varchar(5) NOT NULL · `kbm_jam_selesai` varchar(5) NULL · `kbm_jml_mapel_hari` int(11) NOT NULL · `istirahat` mediumtext NOT NULL · `upacara` text NULL · `libur` int(11) NOT NULL DEFAULT 0. PK (`id_kbm`), AI.

### 2.12 `kelas_jadwal_mapel` (9 kolom)
`id_jadwal` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_kelas` int(11) NULL · `id_hari` int(11) NOT NULL · `jam_ke` int(11) NOT NULL · `id_mapel` int(11) NULL · `dari` varchar(10) NULL · `sampai` varchar(10) NULL. PK (`id_jadwal`), AI.

### 2.13 `kelas_jadwal_materi` (8 kolom)
`id_kjm` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_materi` int(11) NULL · `id_mapel` int(11) NULL · `id_kelas` int(11) NULL · `jadwal_materi` varchar(20) NOT NULL · `jenis` int(11) NULL COMMENT '1=materi, 2=tugas'. PK (`id_kjm`), AI.

### 2.14 `kelas_materi` (18 kolom)
`id_materi` int(11) NOT NULL · `id_tp` int(11) NOT NULL DEFAULT 1 · `id_smt` int(11) NOT NULL DEFAULT 1 · `kode_materi` mediumtext NOT NULL · `id_guru` int(11) NULL · `materi_kelas` mediumtext NOT NULL · `id_mapel` int(11) DEFAULT 0 · `kode_mapel` varchar(300) NULL · `judul_materi` mediumtext NOT NULL · `isi_materi` longtext NOT NULL · `file` longtext NULL · `link_file` varchar(255) NULL · `tgl_mulai` datetime NULL · `created_on` datetime NULL · `updated_on` datetime DEFAULT current_timestamp() · `status` int(11) NULL · `youtube` varchar(255) NOT NULL · `jenis` int(11) NOT NULL DEFAULT 1 COMMENT '1=materi, 2=tugas'. PK (`id_materi`), AI.

### 2.15 `kelas_siswa` (5 kolom)
`id_kelas_siswa` int(11) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `id_siswa` int(11) NULL · `id_kelas` int(11) NULL. PK (`id_kelas_siswa`), UNIQUE `id_kelas_siswa_idx` (`id_kelas_siswa`), UNIQUE `uq_ks_tp_smt_siswa` (`id_tp`,`id_smt`,`id_siswa`), KEY `id_siswa_idx` (`id_siswa`), KEY `Id_kelas` (`id_kelas`). **Tanpa AI.**

### 2.16 `kelas_struktur` (18 kolom)
`id_kelas` int(11) NOT NULL · `ketua` int(11) NULL · `wakil_ketua` int(11) NULL · `sekretaris_1` int(11) NULL · `sekretaris_2` int(11) NULL · `bendahara_1` int(11) NULL · `bendahara_2` int(11) NULL · `sie_ekstrakurikuler` int(11) NULL · `sie_upacara` int(11) NULL · `sie_olahraga` int(11) NULL · `sie_keagamaan` int(11) NULL · `sie_keamanan` int(11) NULL · `sie_ketertiban` int(11) NULL · `sie_kebersihan` int(11) NULL · `sie_keindahan` int(11) NULL · `sie_kesehatan` int(11) NULL · `sie_kekeluargaan` int(11) NULL · `sie_humas` int(11) NULL. PK (`id_kelas`), AI. (Catatan: `id_kelas` di sini PK sekaligus merujuk ke `master_kelas.id_kelas` secara logis — tanpa FK di dump.)

### 2.17 `level_guru` (2 kolom)
`id_level` int(11) NOT NULL · `level` varchar(50) NULL. PK (`id_level`), AI.

### 2.18 `level_kelas` (2 kolom)
`id_level` int(11) NOT NULL · `level` int(11) NULL. PK (`id_level`), KEY `index_id_level` (`id_level`). **Tanpa AI.** (Tabel ini target FK satu-satunya di dump, lihat §6.1.)

### 2.19 `log_materi` (17 kolom)
`id_log` int(11) NOT NULL · `log_time` datetime NOT NULL DEFAULT current_timestamp() · `id_siswa` int(11) NULL · `jam_ke` int(11) NOT NULL · `id_materi` varchar(50) NOT NULL · `id_mapel` int(11) NULL · `log_type` int(11) NOT NULL · `log_desc` mediumtext NOT NULL · `text` longtext NULL · `file` longtext NULL · `nilai` varchar(3) NULL · `catatan` longtext NULL · `ulang` varchar(1) NOT NULL DEFAULT '0' · `address` mediumtext NOT NULL · `agent` mediumtext NOT NULL · `device` mediumtext NOT NULL · `finish_time` varchar(20) NULL. PK (`id_log`), AI.

### 2.20 `login_attempts` (4 kolom)
`id` int(10) UNSIGNED NOT NULL · `ip_address` varchar(45) NOT NULL · `login` varchar(100) NOT NULL · `time` int(10) UNSIGNED NULL. PK (`id`), AI. (`time` adalah unix timestamp integer, bukan datetime.)

### 2.21 `logs` (7 kolom)
`id_log` int(10) UNSIGNED NOT NULL · `id_user` int(11) NULL · `aktivitas` int(11) NOT NULL DEFAULT 0 COMMENT '1=Login, 2=Logout, 3=Create, 4=Read, 5=Update, 6=Delete' · `keterangan` varchar(255) NULL · `ip_address` varchar(45) NULL · `user_agent` text NULL · `waktu` datetime NULL. PK (`id_log`), AI; KEY `id_user`, KEY `waktu`. (Catatan: `LogModel::_ensureTableExists()` di Laravel membuat versi tabel ini dengan kolom yang cocok, tapi tanpa KEY `id_user`/`waktu`.)

### 2.22 `master_ekstra` (3 kolom)
`id_ekstra` int(11) NOT NULL · `nama_ekstra` varchar(100) NOT NULL · `kode_ekstra` varchar(20) NOT NULL. PK (`id_ekstra`), AI.

### 2.23 `master_guru` (47 kolom)
`id_guru` int(11) NOT NULL · `dapo_id` varchar(50) NULL · `ptk_id` varchar(50) NULL · `id_user` int(11) NULL · `nip` char(30) NOT NULL · `nama_guru` varchar(50) NOT NULL · `email` varchar(254) NULL · `kode_guru` varchar(6) NULL · `username` varchar(50) NULL · `password` longtext NULL · `no_ktp` varchar(16) NULL · `tempat_lahir` varchar(30) NULL · `tgl_lahir` date NULL · `jenis_kelamin` varchar(10) NULL · `agama` varchar(10) NULL · `no_hp` varchar(13) NULL · `alamat_jalan` varchar(255) NULL · `rt_rw` varchar(8) NULL · `dusun` varchar(50) NULL · `kelurahan` varchar(50) NULL · `kecamatan` varchar(30) NULL · `kabupaten` varchar(30) NULL · `provinsi` varchar(30) NULL · `kode_pos` int(11) NULL · `kewarganegaraan` varchar(10) NULL · `nuptk` varchar(20) NULL · `jenis_ptk` varchar(50) NULL · `tgs_tambahan` varchar(50) NULL · `status_pegawai` varchar(50) NULL · `status_aktif` varchar(20) NULL · `status_nikah` varchar(20) NULL · `tmt` date NULL · `keahlian_isyarat` varchar(10) NULL · `npwp` varchar(16) NULL · `foto` longtext NULL · `poin_tahunan` decimal(10,2) DEFAULT 0.00 · `api_key` varchar(255) NULL · `ttd` varchar(255) NULL · `dokumen` longtext NULL · `sync_uuid` varchar(36) NULL · `sync_status` enum('synced','pending','conflict') DEFAULT 'pending' · `sync_at` datetime NULL · `sync_origin` enum('pusat','lokal') DEFAULT 'lokal' · `sync_school_uuid` varchar(50) NULL · `deleted_at` datetime NULL · `telegram_id` varchar(50) NULL · `wa_number` varchar(20) NULL COMMENT 'Nomor WhatsApp guru (digit saja, kode negara tanpa 0)'. PK (`id_guru`), AI; UNIQUE `uq_mg_id_user` (`id_user`); KEY `idx_sync_uuid`, KEY `sync_school_uuid`, KEY `idx_deleted_at` (`deleted_at`).

### 2.24 `master_hari_efektif` (2 kolom)
`id_hari_efektif` int(11) NOT NULL · `jml_hari_efektif` int(11) NOT NULL. PK (`id_hari_efektif`), AI. (Tidak ada kolom tahun/semester di tabel ini.)

### 2.25 `master_jurusan` (7 kolom)
`id_jurusan` int(11) NOT NULL · `nama_jurusan` varchar(30) NOT NULL · `kode_jurusan` varchar(10) NULL · `mapel_peminatan` longtext NULL · `status` int(11) NOT NULL DEFAULT 1 · `deletable` int(11) NOT NULL DEFAULT 1 · `sync_school_uuid` varchar(50) NULL. PK (`id_jurusan`), AI; KEY `sync_school_uuid`.

### 2.26 `master_kelas` (18 kolom)
`id_kelas` int(11) NOT NULL · `dapo_id` varchar(50) NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `nama_kelas` varchar(30) NOT NULL · `kode_kelas` varchar(20) NULL · `jurusan_id` int(11) NULL · `level_id` int(11) NOT NULL · `guru_id` int(11) NULL · `siswa_id` int(11) NULL · `jumlah_siswa` longtext NULL · `set_siswa` varchar(1) DEFAULT '0' · `sync_uuid` varchar(36) NULL · `sync_status` enum('synced','pending','conflict') DEFAULT 'pending' · `sync_at` datetime NULL · `sync_origin` enum('pusat','lokal') DEFAULT 'lokal' · `sync_school_uuid` varchar(50) NULL · `deleted_at` datetime NULL. PK (`id_kelas`), AI; KEY `index_level_Id` (`level_id`), KEY `idx_sync_uuid`, KEY `sync_school_uuid`, KEY `idx_deleted_at`; **FK `key_id_cek`: `level_id` → `level_kelas.id_level`** (satu-satunya FK di dump; tanpa aksi ON DELETE/UPDATE).

### 2.27 `master_kelompok_mapel` (5 kolom)
`id_kel_mapel` int(11) NOT NULL · `kode_kel_mapel` varchar(10) NULL · `nama_kel_mapel` varchar(100) NULL · `kategori` varchar(20) NULL · `id_parent` int(11) NULL. PK (`id_kel_mapel`), AI.

### 2.28 `master_mapel` (18 kolom)
`id_mapel` int(11) NOT NULL · `dapo_id` varchar(50) NULL · `nama_mapel` varchar(50) NOT NULL · `kode` varchar(20) NULL · `kelompok` varchar(5) NOT NULL DEFAULT '-' · `bobot_p` int(11) NOT NULL DEFAULT 0 · `bobot_k` int(11) NOT NULL DEFAULT 0 · `jenjang` int(11) NOT NULL DEFAULT 0 · `urutan` int(11) NOT NULL · `status` int(11) NOT NULL DEFAULT 1 · `deletable` int(11) NOT NULL DEFAULT 1 · `urutan_tampil` int(11) NULL · `sync_uuid` varchar(36) NULL · `sync_status` enum('synced','pending','conflict') DEFAULT 'pending' · `sync_at` datetime NULL · `sync_origin` enum('pusat','lokal') DEFAULT 'lokal' · `sync_school_uuid` varchar(50) NULL · `deleted_at` datetime NULL. PK (`id_mapel`), AI; KEY `idx_sync_uuid`, KEY `sync_school_uuid`, KEY `idx_deleted_at`.

### 2.29 `master_siswa` (56 kolom)
`id_siswa` int(11) NOT NULL · `dapo_id` varchar(50) NULL · `nisn` varchar(50) NOT NULL · `nis` varchar(20) NOT NULL · `nama` varchar(50) NOT NULL · `jenis_kelamin` varchar(1) NULL · `username` varchar(50) NOT NULL · `password` text NULL · `kelas_awal` int(11) NULL · `tahun_masuk` varchar(30) NULL · `sekolah_asal` varchar(100) NULL · `tempat_lahir` varchar(100) NULL · `tanggal_lahir` varchar(30) NULL · `agama` varchar(10) NULL · `hp` varchar(15) NULL · `email` varchar(254) NULL · `foto` varchar(255) DEFAULT 'siswa.png' · `anak_ke` int(11) NULL · `status_keluarga` varchar(1) NULL · `alamat` text NULL · `rt` varchar(5) NULL · `rw` varchar(5) NULL · `kelurahan` varchar(100) NULL · `kecamatan` varchar(100) NULL · `kabupaten` varchar(100) NULL · `provinsi` varchar(100) NULL · `kode_pos` int(11) NULL · `nama_ayah` varchar(150) NULL · `tgl_lahir_ayah` varchar(50) NULL · `pendidikan_ayah` varchar(50) NULL · `pekerjaan_ayah` varchar(100) NULL · `nohp_ayah` varchar(20) NULL · `alamat_ayah` longtext NULL · `nama_ibu` varchar(50) NULL · `tgl_lahir_ibu` varchar(50) NULL · `pendidikan_ibu` varchar(50) NULL · `pekerjaan_ibu` varchar(100) NULL · `nohp_ibu` varchar(20) NULL · `alamat_ibu` longtext NULL · `nama_wali` varchar(150) NULL · `tgl_lahir_wali` varchar(50) NULL · `pendidikan_wali` varchar(50) NULL · `pekerjaan_wali` varchar(100) NULL · `nohp_wali` varchar(20) NULL · `alamat_wali` longtext NULL · `nik` varchar(30) NOT NULL · `warga_negara` varchar(20) NOT NULL · `uid` varchar(255) NOT NULL · `sync_uuid` varchar(36) NULL · `sync_status` enum('synced','pending','conflict') DEFAULT 'pending' · `sync_at` datetime NULL · `sync_origin` enum('pusat','lokal') DEFAULT 'lokal' · `sync_school_uuid` varchar(50) NULL · `deleted_at` datetime NULL · `ppdb_status` tinyint(1) DEFAULT 0 · `poin_disiplin` int(11) NOT NULL DEFAULT 100. **PK KOMPOSIT (`id_siswa`,`uid`,`nisn`,`nis`)**, AI pada `id_siswa`; UNIQUE `Id_siswa_idx` (`id_siswa`), UNIQUE `uid_idx` (`uid`), UNIQUE `nisn` (`nisn`); KEY `idx_sync_uuid`, KEY `sync_school_uuid`, KEY `idx_deleted_at`. (Catatan: `tanggal_lahir`/`tgl_lahir_*` bertipe varchar, bukan date — pertahankan.)

### 2.30 `master_smt` (4 kolom)
`id_smt` int(11) NOT NULL · `smt` varchar(10) NOT NULL · `nama_smt` varchar(10) NOT NULL · `active` int(11) NOT NULL. PK (`id_smt`), AI. (`active` int, bukan boolean — kode membandingkan dengan `1`/`'1'`.)

### 2.31 `master_tp` (3 kolom)
`id_tp` int(11) NOT NULL · `tahun` varchar(20) NOT NULL · `active` int(11) NOT NULL. PK (`id_tp`), AI.

### 2.32 `penilaian_guru_periode` (12 kolom)
`id` int(11) UNSIGNED NOT NULL · `nama` varchar(255) NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `tgl_mulai` date NOT NULL · `tgl_selesai` date NOT NULL · `bobot_kehadiran` decimal(5,2) NOT NULL DEFAULT 30.00 · `bobot_mengajar` decimal(5,2) NOT NULL DEFAULT 40.00 · `bobot_piket` decimal(5,2) NOT NULL DEFAULT 30.00 · `status` enum('draft','aktif','selesai') NOT NULL DEFAULT 'draft' · `created_at` datetime NULL · `updated_at` datetime NULL. PK (`id`), AI. (Catatan: `PenilaianGuruModel::_ensureTableExists()` Laravel membuat versi tabel ini — skemanya perlu dicocokkan dengan dump saat migrasi ditulis.)

### 2.33 `penilaian_guru_skor` (11 kolom)
`id` int(11) UNSIGNED NOT NULL · `id_periode` int(11) NOT NULL · `id_guru` int(11) NOT NULL · `skor_kehadiran` decimal(5,2) NOT NULL DEFAULT 0.00 · `skor_mengajar` decimal(5,2) NOT NULL DEFAULT 0.00 · `skor_piket` decimal(5,2) NOT NULL DEFAULT 0.00 · `skor_akhir` decimal(5,2) NOT NULL DEFAULT 0.00 · `predikat` varchar(5) NULL · `detail` longtext NULL · `created_at` datetime NULL · `updated_at` datetime NULL. PK (`id`), AI; UNIQUE `id_periode_id_guru` (`id_periode`,`id_guru`).

### 2.34 `piket_absensi` (10 kolom)
`id` bigint(20) UNSIGNED NOT NULL · `tanggal` date NOT NULL · `id_kelas` int(11) NOT NULL · `id_siswa` int(11) NOT NULL · `status` varchar(5) NOT NULL COMMENT 'H, S, I, A, T' · `keterangan` varchar(255) NULL · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() · `created_by` int(11) NULL · `updated_by` int(11) NULL. PK (`id`), AI; UNIQUE `tanggal_id_siswa` (`tanggal`,`id_siswa`).

### 2.35 `piket_jadwal` (8 kolom)
`id` int(10) UNSIGNED NOT NULL · `id_tp` int(11) NOT NULL · `id_smt` int(11) NOT NULL · `hari` varchar(20) NOT NULL COMMENT 'Senin, Selasa, etc.' · `id_guru` int(11) NOT NULL · `keterangan` text NULL · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(). PK (`id`), AI; KEY `id_tp_id_smt_hari` (`id_tp`,`id_smt`,`hari`).

### 2.36 `piket_kejadian` (14 kolom)
`id` bigint(20) UNSIGNED NOT NULL · `tanggal` date NOT NULL · `jam` time NULL · `jenis` varchar(50) NOT NULL COMMENT 'Pelanggaran, Kejadian, Kecelakaan, Lainnya' · `tingkat` varchar(20) DEFAULT 'ringan' · `pelapor` varchar(255) NULL · `terlapor_siswa_id` int(11) NULL · `terlapor_lain` varchar(255) NULL · `keterangan` text NULL · `penanganan` text NULL · `tindak_lanjut` text NULL · `status` varchar(20) DEFAULT 'belum' · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(). PK (`id`), AI; KEY `tanggal`.

### 2.37 `piket_presensi_guru` (13 kolom)
`id` bigint(20) UNSIGNED NOT NULL · `tanggal` date NOT NULL · `id_kelas` int(11) NOT NULL · `jam_ke` int(11) NOT NULL · `id_mapel` int(11) NOT NULL · `id_guru` int(11) NOT NULL · `status` varchar(10) NOT NULL COMMENT 'H, S, I, A' · `keterangan` text NULL · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() · `is_auto` tinyint(1) DEFAULT 0 · `created_by` int(11) NULL · `updated_by` int(11) NULL. PK (`id`), AI; UNIQUE `uq_ppg_slot` (`tanggal`,`id_kelas`,`jam_ke`,`id_mapel`).

### 2.38 `piket_tamu` (11 kolom)
`id` bigint(20) UNSIGNED NOT NULL · `tanggal` date NOT NULL · `jam_masuk` time NULL · `jam_keluar` time NULL · `nama` varchar(255) NOT NULL · `instansi` varchar(255) NULL · `bertemu` varchar(255) NULL · `keperluan` text NULL · `foto` varchar(255) NULL · `created_at` datetime DEFAULT current_timestamp() · `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(). PK (`id`), AI; KEY `tanggal`.

### 2.39 `post` (7 kolom)
`id_post` int(11) NOT NULL · `dari` int(11) NULL · `dari_group` int(11) NULL · `kepada` varchar(50) NOT NULL COMMENT 'group' · `text` longtext NOT NULL · `tanggal` datetime NOT NULL DEFAULT current_timestamp() · `updated` datetime NOT NULL DEFAULT current_timestamp(). PK (`id_post`), AI. (Perhatikan: `updated` **tanpa** ON UPDATE — berbeda dari `post_comments`/`post_reply`.)

### 2.40 `post_comments` (8 kolom)
`id_comment` int(11) NOT NULL · `id_post` int(11) NULL · `dari` int(11) NULL · `dari_group` int(11) NULL · `text` longtext NOT NULL · `tanggal` datetime NOT NULL DEFAULT current_timestamp() · `updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() · `type` varchar(1) NOT NULL DEFAULT '1' COMMENT '1:pengumuman, 2:materi, 3:tugas'. PK (`id_comment`), AI.

### 2.41 `post_reply` (8 kolom)
`id_reply` int(11) NOT NULL · `id_comment` int(11) NULL · `dari` int(11) NULL · `dari_group` int(11) NULL · `text` longtext NOT NULL · `tanggal` datetime NOT NULL DEFAULT current_timestamp() · `updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() · `type` varchar(1) NOT NULL DEFAULT '1'. PK (`id_reply`), AI.

### 2.42 `setting` (50 kolom)
`id_setting` int(11) NOT NULL · `kode_sekolah` varchar(10) NULL · `sekolah` varchar(50) NULL · `npsn` varchar(10) NULL · `nss` varchar(20) NULL · `jenjang` int(11) NULL · `kepsek` varchar(50) NULL · `nip` varchar(30) NULL · `proktor` varchar(50) NULL · `nip_proktor` varchar(30) NULL · `tanda_tangan` text NULL · `alamat` text NULL · `desa` varchar(100) NULL · `kecamatan` varchar(50) NULL · `kota` varchar(30) NULL · `provinsi` varchar(100) NULL · `kode_pos` int(11) NULL · `telp` varchar(20) NULL · `fax` varchar(20) NULL · `web` varchar(50) NULL · `email` varchar(50) NULL · `nama_aplikasi` varchar(100) NULL · `logo_kanan` text NULL · `logo_kiri` text NULL · `kodescan` text NULL · `kopsekolah` text NULL · `stampel` text NULL · `ttdproktor` text NULL · `banner1` text NULL · `banner2` text NULL · `banner3` text NULL · `versi` varchar(10) NULL · `ip_server` varchar(100) NULL · `waktu` varchar(50) NULL · `server` varchar(50) NULL · `id_server` varchar(50) NULL · `sekolah_id` varchar(50) NULL · `db_versi` varchar(10) NULL · `satuan_pendidikan` varchar(1) NULL · `ba_aktif` varchar(20) NULL · `ba_waktu` datetime NULL · `tkn_siswa` varchar(11) NULL · `mode_app` varchar(11) NULL · `api_key` text NULL · `sync_school_uuid` varchar(50) NULL · `enable_ai` int(1) DEFAULT 1 · `surat_teguran_format` varchar(120) DEFAULT 'SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}' · `surat_teguran_padding` tinyint(1) DEFAULT 3 · `theme_preset` varchar(20) NOT NULL DEFAULT 'default' · `theme_custom` varchar(7) NULL. PK (`id_setting`), AI. Charset dump: `latin1` (satu-satunya tabel latin1).

### 2.43 `users` (22 kolom)
`id` int(10) UNSIGNED NOT NULL · `ip_address` varchar(45) NOT NULL · `username` varchar(100) NULL · `password` varchar(255) NOT NULL · `email` varchar(254) NULL · `activation_selector` varchar(255) NULL · `activation_code` varchar(255) NULL · `forgotten_password_selector` varchar(255) NULL · `forgotten_password_code` varchar(255) NULL · `forgotten_password_time` int(10) UNSIGNED NULL · `remember_selector` varchar(255) NULL · `remember_code` varchar(255) NULL · `created_on` int(10) UNSIGNED NOT NULL · `last_login` int(10) UNSIGNED NULL · `active` tinyint(3) UNSIGNED NULL · `first_name` varchar(50) NULL · `last_name` varchar(50) NULL · `company` varchar(100) NULL · `phone` varchar(20) NULL · `foto` varchar(250) NULL · `jabatan` varchar(50) NULL · `face_descriptor` text NULL. PK (`id`), AI; UNIQUE `id_user` (`id`), UNIQUE `username_idx` (`username`). (Skema Ion Auth klasik: `created_on`/`last_login`/`forgotten_password_time` integer unix timestamp; kolom `tugas_tambahan` yang dibaca `init_helper.php:240-241` **tidak ada** di dump — kode memakai `Schema::hasColumn` guard untuk itu.)

### 2.44 `users_groups` (3 kolom)
`id` int(10) UNSIGNED NOT NULL · `user_id` int(10) UNSIGNED NOT NULL · `group_id` mediumint(8) UNSIGNED NOT NULL. PK (`id`), AI; UNIQUE `uc_users_groups` (`user_id`,`group_id`); KEY `fk_users_groups_users1_idx` (`user_id`), KEY `fk_users_groups_groups1_idx` (`group_id`). (Nama key mengandung `fk_` tapi **bukan** foreign key — tidak ada FK di sini.)

## 3. Empat tabel konflik — dump vs migrasi Laravel

Konvensi: "dump" = CREATE TABLE + ALTER TABLE (PK/index/AI) dari dump mentah.
"Laravel" = file migrasi di `database/migrations/`. Semua migrasi Laravel
memakai `charset utf8mb4 / collation utf8mb4_general_ci`, sedangkan dump
memakai `utf8mb3 / utf8mb3_uca1400_ai_ci` untuk keempat tabel ini.

### 3.1 `absensi_setting` — dump 89 kolom, Laravel 48 kolom (+1 unique). 40 kolom hilang di Laravel; tidak ada kolom ekstra di Laravel.

Isi migrasi Laravel saat ini (`2026_11_09_000012_create_absensi_setting_table.php`):
`increments('id')`; `jam_masuk/jam_pulang` time nullable tanpa default;
`radius_meter` default 100; `foto_wajib` boolean default 0; `hari_kerja`/
`libur_kalender` **json** nullable; `created_at/updated_at` datetime nullable;
`hari_libur_mingguan` string(50) default 'Sun'; token/url WA, field Mikrotik;
`denda_per_menit`/`denda_pulang_awal` decimal(15,2); `min_kehadiran_persen`
decimal(5,2) default 90; `alert_kehadiran_enabled`/`notif_kehadiran_rendah`
boolean default 1; kolom WFH; `qr_scan_*`; `qr_kiosk_token`;
`max_*_per_pengajuan`, `max_sakit_hari`; `kuota_hari_kerja`,
`wajib_jurnal_sebelum_pulang` boolean; `singleton_guard` tinyInteger
`storedAs(1)` + unique `uq_absensi_setting_single_row`;
`pulang_close_dari_tgl`, `wajib_jurnal_tervalidasi_sebelum_pulang`,
`point_lupa_pulang`, `lupa_pulang_dari_tgl`, `denda_alpha_siswa_per_hari`,
`denda_bolos_siswa_per_hari`, `max_denda_siswa_bulan` decimal(10,2).

**Kolom HANYA di dump (40):** `denda_alpha_per_hari`, `denda_alpha_progresif`,
`denda_per_point`, `denda_prog_h3`, `denda_prog_h4`, `denda_prog_h5`,
`denda_prog_h5_step`, `denda_tier_1`, `denda_tier_2`, `denda_tier_3`,
`face_match_mode`, `face_threshold`, `google_client_id`,
`google_client_secret`, `google_login_enabled`, `grace_period`,
`jam_masuk_jumat`, `jam_pulang_jumat`, `max_cuti_hari`, `max_denda_bulan`,
`max_izin_hari`, `point_alpa`, `point_cuti`, `point_hadir`, `point_izin`,
`point_lembur_per_jam`, `point_pulang_awal`, `point_sakit`, `point_telat`,
`point_tier_1`, `point_tier_2`, `point_tier_3`, `reward_cap_menit`,
`reward_point_tier_1`, `reward_point_tier_2`, `reward_point_tier_3`,
`wa_api_key`, `wa_base_url`, `wa_gateway_mode`, `wa_session_name`.

**Perbedaan pada kolom yang sama namanya:**
- `id`: dump `int(10) UNSIGNED NOT NULL AUTO_INCREMENT`, PK — Laravel
  `increments()` setara ✔.
- `jam_masuk` / `jam_pulang`: dump `time DEFAULT '07:00:00'` / `'15:00:00'`
  — Laravel nullable **tanpa default** ✗ (default hilang).
- `radius_meter`: dump `int(11) DEFAULT 50` — Laravel default **100** ✗.
- `foto_wajib`: dump `tinyint(1) DEFAULT 1` — Laravel boolean default **0** ✗
  (nilai default terbalik).
- `hari_kerja`: dump `varchar(255) DEFAULT NULL` — Laravel **json** nullable
  ✗ (tipe salah).
- `libur_kalender`: dump `tinyint(1) DEFAULT 1` — Laravel **json** nullable ✗
  (tipe dan default salah).
- `hari_libur_mingguan`: dump `varchar(32) DEFAULT 'Sun'` — Laravel
  `string(50)` default 'Sun' (panjang berbeda; minor).
- `mikrotik_port`: dump `int(5) DEFAULT 8728` — Laravel integer nullable
  default 8728 ✔ (display width diabaikan).
- `mikrotik_hotspot_profile`: dump `varchar(100) DEFAULT 'default'` — Laravel
  sama ✔.
- `denda_per_menit` / `denda_pulang_awal`: dump `int(11) DEFAULT 1000` —
  Laravel `decimal(15,2)` nullable default 1000.0 ✗ (tipe salah).
- `min_kehadiran_persen`: dump `decimal(5,2) DEFAULT 90.00` — Laravel sama ✔.
- `wfh_mulai` / `wfh_selesai`: dump `time DEFAULT '08:00:00'`/`'16:00:00'` —
  Laravel sama ✔.
- `wfh_max_per_bulan`: dump `int(3) DEFAULT 5` — Laravel integer nullable
  default 5 ✔.
- `wa_gateway_url`: dump `varchar(255) DEFAULT NULL` — Laravel `string(255)`
  nullable **default 'https://api.fonnte.com/send'** ✗ (default tambahan yang
  tidak ada di produksi).
- `singleton_guard`: dump `tinyint(4) GENERATED ALWAYS AS (1) STORED` +
  `UNIQUE KEY uq_absensi_setting_single_row` — Laravel
  `tinyInteger()->storedAs(1)` + unique sama ✔.
- `kuota_hari_kerja`, `wajib_jurnal_sebelum_pulang`: dump `tinyint(1)
  DEFAULT 1` — Laravel boolean nullable default 1 ✔.
- `point_lupa_pulang`: dump `int(11) DEFAULT 5` — Laravel integer nullable
  default 5 ✔. `denda_alpha_siswa_per_hari` / `denda_bolos_siswa_per_hari`:
  dump `int(11) DEFAULT 0` — Laravel sama ✔. `max_denda_siswa_bulan`: dump
  `decimal(10,2) DEFAULT 0.00` — Laravel sama ✔.
- **Index:** dump `PRIMARY KEY (id)` + `UNIQUE (singleton_guard)` — Laravel
  sama ✔. Tidak ada index lain di keduanya.

### 3.2 `absensi_lokasi` — dump 9 kolom, Laravel 9 kolom (nama sama semua). Tidak ada kolom hilang; 3 perbedaan tipe/default.

Isi migrasi Laravel saat ini
(`2026_11_09_000005_create_absensi_lokasi_table.php`): `increments('id')`;
`nama_lokasi` string(100); `lat`/`lng` **double**; `radius_meter` integer
default 100; `keterangan` **string(255)** nullable; `created_at/updated_at`
datetime nullable; `is_utama` boolean nullable default 0.
Perbedaan:
- `id`: dump `int(10) UNSIGNED NOT NULL AUTO_INCREMENT`, PK — Laravel
  `increments()` ✔.
- `nama_lokasi`: dump `varchar(100) NOT NULL` — Laravel sama ✔.
- `lat`: dump `decimal(10,8) NOT NULL` — Laravel `double` NOT NULL ✗.
- `lng`: dump `decimal(11,8) NOT NULL` — Laravel `double` NOT NULL ✗.
- `radius_meter`: dump `int(11) DEFAULT 50` — Laravel default **100** ✗.
- `keterangan`: dump `text DEFAULT NULL` — Laravel `string(255)` nullable ✗.
- `is_utama`: dump `tinyint(1) DEFAULT 0` — Laravel boolean nullable default 0
  (hanya beda nullable).
- **Index:** dump hanya `PRIMARY KEY (id)` — Laravel sama ✔.

### 3.3 `absensi_harian` — dump 41 kolom, Laravel 35 kolom. 6 kolom hilang di Laravel; tidak ada kolom ekstra.

Isi migrasi Laravel saat ini
(`2026_11_09_000002_create_absensi_harian_table.php`): `bigIncrements('id')`;
`user_id`/`id_guru` unsignedInteger; `tgl` date; `jam_masuk`/`jam_pulang`
datetime nullable; `lat/lng` **double** nullable; `lokasi_*_id`, `jarak_*`
integer nullable; `foto_*` **string(255)** nullable; `status_*` **string(20)**
nullable; `created_at/updated_at` datetime nullable; `ip_address`
**string(45)** nullable; `device_info` **text** nullable; `telat_menit`,
`pulang_cepat_menit`, `datang_awal_menit`, `pulang_akhir_menit` integer default
0; `gps_accuracy` float(24) nullable; `is_mock_location` boolean default 0;
`denda` **decimal(10,2)** default 0; `keterangan_denda`, `keterangan` text
nullable; `is_wfh` boolean nullable default 0; `izin_id` unsignedInteger
nullable; `pulang_tidak_tercatat` boolean default 0; `poin_penalti_pulang`
integer default 0; index `idx_user_tgl`, `idx_tgl_status`, `idx_tgl_userid`,
`idx_denda`, unique `uq_user_tgl`, `idx_harian_izin_id`.
**Kolom HANYA di dump (6):** `approved_at`, `approved_by`, `catatan`,
`points`, `verified_at`, `verified_by`. (Dump: `verified_by int(11) NULL`,
`verified_at datetime NULL`, `approved_by int(11) NULL`, `approved_at
datetime NULL`, `catatan text NULL`, `points int(11) DEFAULT 0`.)
**Perbedaan pada kolom yang sama namanya:**
- `id`: dump `int(10) UNSIGNED NOT NULL AUTO_INCREMENT`, PK — Laravel
  `bigIncrements` (bigint unsigned) ✗ (tipe PK berbeda; aman secara fungsi
  tapi tidak identik).
- `user_id`: dump `int(11) NOT NULL` (**signed**) — Laravel `unsignedInteger`
  ✗ (signedness berbeda).
- `id_guru`: dump `int(11) UNSIGNED DEFAULT 0` — Laravel unsignedInteger NOT
  NULL **tanpa default** ✗ (default 0 hilang).
- `lat_masuk`/`lat_pulang`: dump `decimal(10,8) DEFAULT NULL` — Laravel
  `double` nullable ✗. `lng_masuk`/`lng_pulang`: dump `decimal(11,8)` —
  Laravel `double` ✗.
- `foto_masuk`/`foto_pulang`: dump `text DEFAULT NULL` — Laravel `string(255)`
  nullable ✗.
- `status_masuk`/`status_pulang`: dump `varchar(50) DEFAULT NULL` — Laravel
  `string(20)` nullable ✗ (panjang dipotong 50→20).
- `denda`: dump `decimal(15,2) DEFAULT 0.00` — Laravel `decimal(10,2)`
  default 0 ✗ (presisi dipotong 15→10).
- `ip_address`: dump `varchar(255) DEFAULT NULL` — Laravel `string(45)`
  nullable ✗. `device_info`: dump `varchar(255) DEFAULT NULL` — Laravel
  `text` nullable ✗.
- `gps_accuracy`: dump `float DEFAULT NULL` — Laravel `float(24)` nullable ✔.
- `is_wfh`: dump `tinyint(1) DEFAULT 0` — Laravel boolean nullable default 0
  (beda nullable). `is_mock_location`: dump `tinyint(1) DEFAULT 0` — Laravel
  boolean default 0 ✔.
- `telat_menit`, `pulang_cepat_menit`, `datang_awal_menit`,
  `pulang_akhir_menit`: dump `int(11) DEFAULT 0` — Laravel integer default 0
  ✔. `izin_id`: dump `int(11) UNSIGNED NULL` — Laravel unsignedInteger
  nullable ✔. `pulang_tidak_tercatat`: dump `tinyint(1) NOT NULL DEFAULT 0`
  — Laravel boolean default 0 ✔. `poin_penalti_pulang`: dump `int(11) NOT
  NULL DEFAULT 0` — Laravel integer default 0 ✔.
- **Index:** dump `PRIMARY KEY (id)`, `UNIQUE uq_user_tgl (user_id,tgl)`,
  `KEY idx_tgl_status (tgl,status_masuk)`, `KEY idx_user_tgl (user_id,tgl)`,
  `KEY idx_tgl_userid (tgl,user_id,status_masuk)`, `KEY idx_denda
  (user_id,denda)`, `KEY idx_harian_izin_id (izin_id)` — Laravel punya
  **keenam** nama index itu ✔. Tidak ada FK di keduanya.

### 3.4 `absensi_izin` — dump 17 kolom, Laravel 11 kolom. 6 kolom hilang di Laravel; tidak ada kolom ekstra.

Isi migrasi Laravel saat ini
(`2026_11_09_000003_create_absensi_izin_table.php`): `bigIncrements('id')`;
`user_id`/`id_guru` unsignedInteger; `tgl_mulai`/`tgl_selesai` date; `jenis`
string(20) default 'ijin'; `keterangan` text nullable; `status`
**enum('pending','approved','rejected')** default 'pending';
`created_at/updated_at` datetime nullable; `bukti` **text** nullable; index
`idx_izin_user_status (user_id,status,tgl_mulai)`.
**Kolom HANYA di dump (6):** `approved_by` (`int(11) DEFAULT NULL`),
`kategori_cuti` (`varchar(30) DEFAULT NULL`), `verified_at` (`datetime
DEFAULT NULL`), `verified_by` (`int(11) DEFAULT NULL`), `verifikasi`
(`varchar(255) NOT NULL` — **tanpa default**; jebakan saat migrasi: kolom
NOT NULL tanpa default), `verifikasi_catatan` (`text DEFAULT NULL`).
**Perbedaan pada kolom yang sama namanya:**
- `id`: dump `int(10) UNSIGNED NOT NULL AUTO_INCREMENT`, PK — Laravel
  `bigIncrements` ✗.
- `user_id`: dump `int(11) NOT NULL` (signed) — Laravel unsignedInteger ✗.
- `id_guru`: dump `int(11) UNSIGNED DEFAULT 0` — Laravel unsignedInteger tanpa
  default ✗.
- `tgl_mulai`/`tgl_selesai`: dump `date NOT NULL` — Laravel sama ✔.
- `jenis`: dump `varchar(20) NOT NULL DEFAULT 'ijin'` — Laravel string(20)
  default 'ijin' ✔.
- `status`: dump `varchar(20) DEFAULT 'pending'` — Laravel
  `enum('pending','approved','rejected')` default 'pending' ✗ (migrasi
  menambahkan constraint enum yang tidak ada di produksi).
- `bukti`: dump `varchar(200) DEFAULT NULL` — Laravel `text` nullable ✗.
- `keterangan`: dump `text DEFAULT NULL` — Laravel sama ✔.
  `created_at/updated_at`: dump `datetime DEFAULT NULL` — Laravel sama ✔.
- **Index:** dump `PRIMARY KEY (id)` + `KEY idx_izin_user_status
  (user_id,status,tgl_mulai)` — Laravel sama ✔.

## 4. `user_notifikasi` — kedua kolom WA ada di produksi dan hilang di Laravel

**Dump** (10 kolom, `utf8mb4_general_ci`): `id int(11) UNSIGNED NOT NULL` (PK,
AUTO_INCREMENT), `user_id int(11) UNSIGNED NOT NULL`, `telegram_chat_id
varchar(50) DEFAULT NULL`, **`wa_number` varchar(20) DEFAULT NULL**,
**`wa_verified` tinyint(1) DEFAULT 0**, `prefs text DEFAULT NULL`,
`link_token varchar(64) DEFAULT NULL`, `link_expires datetime DEFAULT NULL`,
`created_at datetime DEFAULT NULL`, `updated_at datetime DEFAULT NULL`;
index: `PRIMARY KEY (id)`, `UNIQUE KEY user_id (user_id)`.
**Migrasi Laravel saat ini**
(`2026_11_09_000051_create_user_notifikasi_table.php`, 8 kolom):
`increments('id')`; `user_id` unsignedInteger; `telegram_chat_id` string(50)
nullable; `prefs` text nullable; `link_token` string(64) nullable;
`link_expires` datetime nullable; `created_at/updated_at` datetime nullable;
`unique('user_id')`.
**Yang hilang:** `wa_number` (varchar(20), nullable) dan `wa_verified`
(tinyint(1), default 0). Selain itu tidak ada perbedaan. Catatan:
`master_guru` juga punya kolom `wa_number varchar(20)` di dump — konsisten
sebagai pola penamaan WA di aplikasi ini.

## 5. Kasus `kelas` — tabel yang memang tidak ada; `$table = 'kelas'` adalah artefak porting

**Bukti:**
1. **Dump tidak punya tabel `kelas`.** Tidak ada `CREATE TABLE \`kelas\`` di
   dump. Yang ada: `master_kelas` dan 10 tabel `kelas_*`.
2. **Tidak ada view/alias.** `CREATE VIEW` di dump = 0.
3. **Satu-satunya referensi ke tabel `kelas`** adalah `protected $table =
   'kelas';` di `app/Models/KelasModel.php:18` Laravel (dengan komentar
   "`$table` diisi tabel utama yang dipakai model tersebut (kelas)"). CI4
   `app/Models/KelasModel.php` **tidak** mendeklarasikan `$table` sama sekali.
4. **Nol query ke tabel `kelas`.** Tidak ada `DB::table('kelas')`,
   `join('kelas')`, `hasTable('kelas')`, `tableExists('kelas')`, atau
   `from('kelas')` di mana pun di kedua repo.
5. **Tidak ada method `KelasModel` yang memakai `$table` implisit.** Semua
   method memakai `DB::table('…')` eksplisit; tidak ada `static::query()`,
   `$this->where(…)`, atau `$this->db->table($this->table)`. Contoh:
   `getKelasList()` (`KelasModel.php:289-293`) query
   `DB::table('master_kelas as a')`; `get_one()` (:354-357) query
   `DB::table('master_kelas as a')` + join `master_jurusan` dan `level_kelas`;
   `getNamaKelasByNama()` (:446-448) query `DB::table('master_kelas')`.
6. **Klaim `PORTING.md` tidak terverifikasi.** `PORTING.md` menyebut `kelas`
   "hanya muncul sebagai target `addColumn`". Pencarian `addColumn('kelas')`
   di `app/Database/Migrations/` CI4 menghasilkan **0 hit**; satu-satunya
   kemunculan kata `kelas` di migrasi CI4 adalah kunci array `'kelas'` di
   `20261101000000_NormalizeJabatanGuruKeys.php:145,152,154` (bukan nama
   tabel).
**Kesimpulan:** `kelas` **bukan** alias/view dari `master_kelas` dan bukan
tabel yang hilang dari dump — melainkan nama tabel yang **tidak pernah
ada**; `$table = 'kelas'` di `KelasModel` Laravel adalah tebakan porting
yang tidak pernah dieksekusi karena semua method mengabaikannya.
**Jangan membuat migrasi untuk tabel `kelas`.** Perbaikan yang disarankan
(di luar cakupan tugas ini — file model tidak diubah): ubah `$table`
`KelasModel` menjadi `'master_kelas'` atau hapus deklarasinya.

## 6. Kasus CBT — database/aplikasi terpisah

**Bukti:**
1. **Nol tabel `cbt_*` di dump.** Dari 7 tabel yang ditanyakan (`cbt_siswa`,
   `cbt_soal`, `cbt_jadwal`, `cbt_nilai`, `cbt_jawaban`, `cbt_sesi_siswa`,
   `cbt_rekap_jadwal`): tidak satu pun ada di dump maupun di migrasi Laravel.
2. **Migrasi CI4 tidak pernah membuat tabel CBT itu.** Satu-satunya
   `createTable('cbt_…')` di seluruh `app/Database/Migrations/` CI4 adalah
   `cbt_rekap_siswa` (`2026-01-15-155500_AddCbtRekapColumns.php:103`,
   kondisional/bila belum ada). Semua migrasi CBT lain hanya
   `addColumn`/`addIndex` ke tabel yang diasumsikan **sudah ada**. Artinya
   skema CBT dibuat di luar repo ini.
3. **Kode CI4 justru banyak memakai tabel CBT itu** (di luar migrasi):
   `cbt_soal` 11×, `cbt_nilai` 7×, `cbt_jadwal` 7×, `cbt_ruang` 6×,
   `cbt_pakta_integritas` 6×, `cbt_jenis` 6×, `cbt_berita_acara` 5×,
   `cbt_jawaban` 4×, `cbt_token` 3×, `cbt_siswa` 3×, `cbt_sesi` 3×,
   `cbt_pengawas` 3×, `cbt_nomor_peserta` 2×, `cbt_kop_kartu` 2×,
   `cbt_bank_soal` 2×, `cbt_kop_berita` 1×, `cbt_kop_absensi` 1× — mayoritas
   di `Models/CbtModel.php`. Jadi aplikasi ini **berjalan dengan asumsi tabel
   CBT ada di database yang sama** di lingkungan asal, tetapi dump produksi
   `absen-ikas` tidak memuatnya.
4. **Arsitektur sync Pusat↔Lokal.** `config/sync.php:36-62` (Laravel; sama di
   CI4 `app/Config/Sync.php:96`) mendaftarkan tabel CBT sebagai objek
   sinkronisasi antar dua ujung: `push_to_local` (Pusat → Lokal):
   `master_siswa, master_kelas, master_mapel, master_guru, cbt_jenis,
   cbt_bank_soal, cbt_soal, cbt_jadwal, cbt_sesi, cbt_ruang, cbt_sesi_siswa,
   kelas_siswa`; `push_to_pusat` (Lokal → Pusat): `cbt_siswa, cbt_jawaban,
   cbt_nilai, cbt_rekap_siswa, cbt_rekap_jadwal`. `Libraries/SyncService.php`
   melakukan `upsert` ke `cbt_jadwal, cbt_bank_soal, cbt_soal, cbt_ruang,
   cbt_sesi, cbt_sesi_siswa` dan membaca `cbt_siswa, cbt_jawaban`. Ini pola
   sinkronisasi dengan **sistem CBT pusat yang terpisah**.
5. Konsisten dengan `PORTING.md`: baris CBT di file `cronjob`
   (`cbt:proses_scoring_queue`, dll.) milik aplikasi ujian terpisah — tidak
   di-port. (Juga konsisten dengan konteks pengguna: sistem CBT sekolah
   berjalan di `sts.ikas.my.id` sebagai aplikasi terpisah.)
**Kesimpulan:** CBT adalah aplikasi/database terpisah (atau setidaknya skema
terpisah) yang disinkronkan ke aplikasi absen; tabel-tabel `cbt_*` **tidak
boleh** dibuat sebagai migrasi dari dump ini (tidak ada sumber skemanya di
sini). Yang bisa dilakukan: (a) biarkan migrasi `cbt_rekap_siswa` Laravel apa
adanya (satu-satunya tabel CBT yang skemanya ada di repo CI4); (b) bila
sinkronisasi CBT mau dihidupkan, ambil skema dari database/aplikasi CBT itu
sendiri, bukan dari dump `absen-ikas`. Perhatian: `SyncService` Laravel akan
gagal saat menyentuh tabel `cbt_*` yang tidak ada sampai sumber itu tersedia.

## 7. Catatan tipe data

### 7.1 Foreign key — ada 1 FK di dump
`master_kelas.level_id` → `level_kelas.id_level`, constraint `key_id_cek`.
Tidak ada aksi `ON DELETE/UPDATE` (default RESTRICT/NO ACTION). Ini
satu-satunya FK di seluruh database — jadi urutan migrasi hanya krusial untuk
pasangan ini (`level_kelas` sebelum `master_kelas`, atau tambahkan FK
belakangan).

### 7.2 Inventaris tipe (jumlah kolom di 91 tabel)
`int` 372, `varchar` 332, `datetime` 130, `text` 74, `tinyint` 48, `decimal`
37, `date` 25, `time` 24, `longtext` 23, `enum` 22, `mediumtext` 14,
`smallint` 11, `bigint` 9, `double` 2, `mediumint` 2, `float` 1, `blob` 1,
`char` 1, `year` 1. **Tidak ada kolom `json` sama sekali** di dump — semua
`json()` di migrasi Laravel (mis. `absensi_setting.hari_kerja`/
`libur_kalender`) adalah kesalahan terjemahan dari `varchar`/`tinyint`.

> Inventaris ini masih menghitung tabel `alumni` (18 kolom) yang ada di dump
> produksi, padahal tabel tersebut sudah tidak di-port sejak 10 Nov 2026
> (lihat §1.1) — dump produksi adalah sumber skema, bukan cermin port ini.

### 7.3 Enum (16 tabel, 22 kolom) — pakai `$table->enum()` dengan nilai persis
- `absensi_koreksi.status`: pending, approved, rejected
- `absensi_rekap_semester.status_kelayakan`: layak, perhatian, tidak_layak
- `alumni.jenis_kelamin`: L, P (tidak lagi di-port — tracer study dihapus 10 Nov 2026)
- `dapodik_log.entitas`: all, sekolah, gtk, siswa, rombel, mapel,
  pembelajaran, nilai, ruang; `.metode`: api, offline_json, offline_excel;
  `.status`: sukses, gagal, parsial
- `disiplin_kategori.jenis`: PELANGGARAN, PENGHARGAAN
- `face_registration_requests.status`: pending, approved, rejected
- `master_guru` / `master_kelas` / `master_mapel` / `master_siswa` —
  masing-masing `.sync_status`: synced, pending, conflict; `.sync_origin`:
  pusat, lokal
- `penilaian_guru_periode.status`: draft, aktif, selesai
- `poin_siswa_history.tipe`: PELANGGARAN, PENGHARGAAN, KOREKSI, PRESENSI
- `presensi_bdr.lingkup`: SEMUA, TINGKAT, KELAS
- `presensi_lembur.status`: PENDING, APPROVED, REJECTED
- `reward_redemptions.status`: PENDING, APPROVED, REJECTED, CLAIMED
- `system_update_log.status`: downloaded, installed, failed, rolled_back
  (tabel tidak direferensikan kode)

### 7.4 Decimal — presisi harus dipertahankan
- Koordinat: `decimal(10,8)` lat / `decimal(11,8)` lng di `absensi_harian`
  (masuk+pulang), `absensi_lokasi`; `presensi_siswa` memakai
  `decimal(10,7)`/`decimal(11,7)` (7 digit, bukan 8 — jangan diseragamkan).
  Migrasi Laravel yang ada memakai `double` untuk semuanya — salah.
- Uang: `decimal(15,2)` di `absensi_harian.denda`,
  `absensi_pembayaran_denda.total_denda/nominal_bayar`,
  `absensi_saldo_denda.nominal/saldo_akhir`, `rewards.nominal`;
  `decimal(12,2)` `absensi_rekap_semester.total_denda`; `decimal(10,2)` di
  `absensi_setting.denda_per_point/max_denda_bulan/max_denda_siswa_bulan`,
  `master_guru.poin_tahunan`, `presensi_siswa.denda`.
- Persen/bobot/skor: `decimal(5,2)` di
  `absensi_setting.min_kehadiran_persen`, `denda_prog_h3/h4/h5/h5_step`,
  `penilaian_guru_periode.bobot_*`, `penilaian_guru_skor.skor_*`,
  `presensi_lembur.total_jam`; `decimal(4,2)`
  `absensi_setting.face_threshold` (0.72).

### 7.5 Timestamp/datetime dengan default
Kolom `datetime DEFAULT current_timestamp()` dan/atau `ON UPDATE
current_timestamp()` (sebagian **bukan** nullable): `guru_jadwal_pribadi.created_at`
(default saja), `.updated_at` (default + on update);
`kelas_catatan_mapel.tgl` (`datetime NOT NULL DEFAULT current_timestamp()`),
`kelas_catatan_wali.tgl` (NOT NULL, default + on update);
`kelas_materi.updated_on` (default saja), `log_materi.log_time` (`NOT NULL
DEFAULT current_timestamp()`); `piket_absensi/piket_jadwal/piket_kejadian/
piket_presensi_guru/piket_tamu`: `created_at` default saja + `updated_at`
default + on update; `post.tanggal` dan `.updated` (`NOT NULL DEFAULT
current_timestamp()` — `updated` **tanpa** on-update),
`post_comments.tanggal`/`post_reply.tanggal` (NOT NULL default) dan `.updated`
(NOT NULL default + on update); `post_likes.created_at` (`NOT NULL DEFAULT
current_timestamp()`), `api_token.timestamp` (tabel tak direferensikan),
`absensi_koreksi.created_at` (default saja). Sebaliknya, sebagian besar
`created_at/updated_at` lain di dump adalah `datetime DEFAULT NULL` (nullable,
tanpa default) — jangan otomatis pakai `$table->timestamps()` (yang membuat
kolom `timestamp`, bukan `datetime`).

### 7.6 Int/unsigned/tinyint
- **Unsigned vs signed penting:** banyak PK/FK `int(10)/(11) UNSIGNED`
  (`absensi_*`, `users.id`, `groups.id mediumint(8) UNSIGNED`,
  `login_attempts.id int(10) UNSIGNED`, `penilaian_guru_* .id int(11)
  UNSIGNED`) vs kolom `int(11)` **signed** (`user_id` di `absensi_harian`/
  `absensi_izin`, sebagian besar `id_*` di tabel master/kelas). Jangan
  samakan jadi `unsignedInteger` semua.
- **`tinyint(1)` = boolean** (48 kolom) — tapi ada juga `tinyint(3) UNSIGNED`
  (`users.active`) dan `tinyint(4)` (`absensi_setting.singleton_guard`,
  generated) yang **bukan** boolean.
- **Display width `int(11)` vs `int(10)` vs `int(5)`/`int(3)`** tidak
  berpengaruh di MariaDB 10.11 — abaikan saat menulis migrasi.
- **Bigint PK:** `id bigint(20) UNSIGNED` (AI) di `activity_log`,
  `guru_jurnal.id_jurnal`, `piket_absensi`, `piket_kejadian`,
  `piket_presensi_guru`, `piket_tamu`, `poin_history.id`,
  `system_update_log.id`; `system_updates.file_size bigint(20) UNSIGNED
  DEFAULT 0`. → `$table->bigIncrements` / `unsignedBigInteger`.
- **Smallint/mediumint:** `smallint(4)` (8 kolom di
  `absensi_rekap_semester`, `tahun`/`jumlah_*` di `absensi_surat_panggilan` —
  yang terakhir `smallint(5) UNSIGNED`); `mediumint(8) UNSIGNED` di
  `groups.id` dan `users_groups.group_id`.
- **Tipe langka lain:** `float` hanya `absensi_harian.gps_accuracy`; `double`
  hanya `absensi_qr_log.lat/lng`; `blob` hanya `ci_sessions.data` (tabel tak
  direferensikan); `char(30)` hanya `master_guru.nip` (jangan jadi
  `string(30)` — `char` vs `varchar` berbeda di padding/perbandingan);
  `year(4)` hanya `alumni.tahun_lulus` (→ `$table->year()`; tabel `alumni`
  sendiri sudah tidak di-port sejak 10 Nov 2026).

### 7.7 Text/mediumtext/longtext — jangan turunkan ke `string(255)`
- `mediumtext` (14 kolom): `api_token.address/agent/device/token`,
  `kelas_catatan_mapel.text`, `kelas_catatan_wali.text`,
  `kelas_jadwal_kbm.istirahat`, `kelas_materi.kode_materi/materi_kelas/
  judul_materi`, `log_materi.log_desc/address/agent/device`.
- `longtext` (23 kolom): `jabatan_guru.mapel_kelas/ekstra_kelas`,
  `kelas_catatan_mapel.reading`, `kelas_catatan_wali.reading`,
  `kelas_ekstra.ekstra`, `kelas_materi.isi_materi/file`,
  `log_materi.text/file/catatan`, `master_guru.password/foto/dokumen`,
  `master_jurusan.mapel_peminatan`, `master_kelas.jumlah_siswa`,
  `master_siswa.alamat_ayah/alamat_ibu/alamat_wali`,
  `penilaian_guru_skor.detail`, `post.text`, `post_comments.text`,
  `post_reply.text`, `sync_jobs.payload`.
- Perhatian: `master_guru.password` dan `master_siswa.password` bertipe
  `longtext`/`text` (bukan `varchar(255)` seperti `users.password`) —
  pertahankan apa adanya, jangan "perbaiki".

### 7.8 Kolom generated & charset
- **Satu-satunya generated column:** `absensi_setting.singleton_guard
  tinyint(4) GENERATED ALWAYS AS (1) STORED` + unique — Laravel sudah benar
  (`tinyInteger()->storedAs(1)` + unique).
- **Charset bervariasi per tabel** (migrasi Laravel menyeragamkan ke
  `utf8mb4_general_ci`, dump tidak): `utf8mb3_uca1400_ai_ci` 52 tabel;
  `utf8mb4_general_ci` 21 tabel; `utf8mb4_uca1400_ai_ci` 8;
  `utf8mb4_unicode_ci` 5; `utf8mb3_general_ci` 4; **`latin1_swedish_ci` 1
  tabel: `setting`** — satu-satunya tabel latin1; bila migrasi ditulis dengan
  `utf8mb4`, isi `setting` (teks sekolah) tetap aman ASCII pada umumnya, tapi
  ini perlu dicatat sebagai penyimpangan sadar.

### 7.9 Lain-lain
- **Tidak ada `DEFAULT '0000-00-00'`** (zero-date) di dump; tidak ada kolom
  `datetime/date/time NOT NULL` tanpa default yang bermasalah kecuali
  `absensi_izin.verifikasi varchar(255) NOT NULL` tanpa default — untuk kolom
  `NOT NULL` tanpa default lain, penulis migrasi cukup tidak memberi
  `->nullable()`/`->default()`.
- **PK komposit:** `master_siswa PRIMARY KEY (id_siswa, uid, nisn, nis)` +
  unique terpisah `id_siswa`, `uid`, `nisn` — PK 4 kolom yang tidak bisa
  diwakili `$table->id()`; butuh `$table->primary([...])`.
- **Unique key bernama aneh yang perlu dipertahankan namanya bila kode
  mengandalkannya:** `kelas_siswa.uq_ks_tp_smt_siswa`,
  `jabatan_guru.uniq_jabatan_guru_periode`,
  `penilaian_guru_skor.id_periode_id_guru`,
  `piket_presensi_guru.uq_ppg_slot`, `piket_absensi.tanggal_id_siswa`,
  `master_guru.uq_mg_id_user`, `users_groups.uc_users_groups`,
  `users.username_idx`, `system_updates.uk_version` (tabel tak direferensikan).
- **PK tanpa AUTO_INCREMENT:** `jabatan_guru.id_jabatan_guru varchar(50)`
  (PK, bukan AI), `kelas_ekstra.id_kelas_ekstra varchar(50)` (PK, bukan AI),
  `buku_induk.id_siswa` (PK, bukan AI), `users_profile.id_user` (PK, AI —
  tabel tak direferensikan), `ci_sessions.id` (PK varchar? — cek skema bila
  jadi dibuat; tabel tak direferensikan).

## 8. Migrasi yang dibuat/diperbaiki

Dibuat/diperbaiki oleh worker migrasi (9 Nov 2026 malam) dari skema §2–§4;
diverifikasi independen di §9. Tidak ada file migrasi lain yang diubah.

### 8.1 44 file migrasi baru (2026_11_09_000053–000096)

| No. | File | Tabel |
|---|---|---|
| 000053 | `2026_11_09_000053_create_users_table.php` | `users` |
| 000054 | `2026_11_09_000054_create_groups_table.php` | `groups` |
| 000055 | `2026_11_09_000055_create_users_groups_table.php` | `users_groups` |
| 000056 | `2026_11_09_000056_create_setting_table.php` | `setting` |
| 000057 | `2026_11_09_000057_create_level_kelas_table.php` | `level_kelas` |
| 000058 | `2026_11_09_000058_create_master_kelas_table.php` | `master_kelas` |
| 000059 | `2026_11_09_000059_create_master_siswa_table.php` | `master_siswa` |
| 000060 | `2026_11_09_000060_create_master_guru_table.php` | `master_guru` |
| 000061 | `2026_11_09_000061_create_level_guru_table.php` | `level_guru` |
| 000062 | `2026_11_09_000062_create_master_mapel_table.php` | `master_mapel` |
| 000063 | `2026_11_09_000063_create_master_kelompok_mapel_table.php` | `master_kelompok_mapel` |
| 000064 | `2026_11_09_000064_create_master_jurusan_table.php` | `master_jurusan` |
| 000065 | `2026_11_09_000065_create_master_tp_table.php` | `master_tp` |
| 000066 | `2026_11_09_000066_create_master_smt_table.php` | `master_smt` |
| 000067 | `2026_11_09_000067_create_master_ekstra_table.php` | `master_ekstra` |
| 000068 | `2026_11_09_000068_create_master_hari_efektif_table.php` | `master_hari_efektif` |
| 000069 | `2026_11_09_000069_create_jabatan_guru_table.php` | `jabatan_guru` |
| 000070 | `2026_11_09_000070_create_kelas_siswa_table.php` | `kelas_siswa` |
| 000071 | `2026_11_09_000071_create_kelas_struktur_table.php` | `kelas_struktur` |
| 000072 | `2026_11_09_000072_create_buku_induk_table.php` | `buku_induk` |
| 000073 | `2026_11_09_000073_create_login_attempts_table.php` | `login_attempts` |
| 000074 | `2026_11_09_000074_create_logs_table.php` | `logs` |
| 000075 | `2026_11_09_000075_create_bulan_table.php` | `bulan` |
| 000076 | `2026_11_09_000076_create_absensi_jadwal_kerja_table.php` | `absensi_jadwal_kerja` |
| 000077 | `2026_11_09_000077_create_guru_jurnal_table.php` | `guru_jurnal` |
| 000078 | `2026_11_09_000078_create_guru_jadwal_pribadi_table.php` | `guru_jadwal_pribadi` |
| 000079 | `2026_11_09_000079_create_kelas_jadwal_mapel_table.php` | `kelas_jadwal_mapel` |
| 000080 | `2026_11_09_000080_create_kelas_jadwal_kbm_table.php` | `kelas_jadwal_kbm` |
| 000081 | `2026_11_09_000081_create_kelas_jadwal_materi_table.php` | `kelas_jadwal_materi` |
| 000082 | `2026_11_09_000082_create_kelas_materi_table.php` | `kelas_materi` |
| 000083 | `2026_11_09_000083_create_log_materi_table.php` | `log_materi` |
| 000084 | `2026_11_09_000084_create_kelas_catatan_mapel_table.php` | `kelas_catatan_mapel` |
| 000085 | `2026_11_09_000085_create_kelas_catatan_wali_table.php` | `kelas_catatan_wali` |
| 000086 | `2026_11_09_000086_create_kelas_ekstra_table.php` | `kelas_ekstra` |
| 000087 | `2026_11_09_000087_create_penilaian_guru_periode_table.php` | `penilaian_guru_periode` |
| 000088 | `2026_11_09_000088_create_penilaian_guru_skor_table.php` | `penilaian_guru_skor` |
| 000089 | `2026_11_09_000089_create_piket_jadwal_table.php` | `piket_jadwal` |
| 000090 | `2026_11_09_000090_create_piket_kejadian_table.php` | `piket_kejadian` |
| 000091 | `2026_11_09_000091_create_piket_absensi_table.php` | `piket_absensi` |
| 000092 | `2026_11_09_000092_create_piket_presensi_guru_table.php` | `piket_presensi_guru` |
| 000093 | `2026_11_09_000093_create_piket_tamu_table.php` | `piket_tamu` |
| 000094 | `2026_11_09_000094_create_post_table.php` | `post` |
| 000095 | `2026_11_09_000095_create_post_comments_table.php` | `post_comments` |
| 000096 | `2026_11_09_000096_create_post_reply_table.php` | `post_reply` |

Urutan file mengikuti §2 (tabel master/level dulu, lalu kelas, lalu piket/post),
dengan `level_kelas` (000057) sebelum `master_kelas` (000058) karena FK
`key_id_cek` (§7.1). Semua migrasi memakai `engine InnoDB`,
`charset utf8mb4 / collation utf8mb4_general_ci` — kecuali `setting`
(000056) yang memakai `latin1`, sesuai dump (§7.8; terverifikasi di §9.5).

### 8.2 5 file migrasi lama yang diedit

- **000002 `absensi_harian`** (35 → 41 kolom): `id` `bigIncrements` →
  `increments`; `user_id` unsigned → signed `integer`; `id_guru` diberi
  default 0; `lat/lng` (masuk+pulang) `double` → `decimal(10,8)/(11,8)`;
  `foto_*` `string(255)` → `text`; `status_*` `string(20)` → `string(50)`;
  `denda` `decimal(10,2)` → `decimal(15,2)`; `ip_address`/`device_info`
  `string(45)`/`text` → `string(255)`; ditambah 6 kolom yang hilang:
  `verified_by`, `verified_at`, `approved_by`, `approved_at`, `catatan`,
  `points`.
- **000003 `absensi_izin`** (11 → 17 kolom): `id` `bigIncrements` →
  `increments`; `user_id` unsigned → signed `integer`; `id_guru` diberi
  default 0; `status` `enum('pending','approved','rejected')` → `string(20)`
  default `'pending'`; `bukti` `text` → `string(200)`; ditambah 6 kolom yang
  hilang: `approved_by`, `kategori_cuti`, `verified_at`, `verified_by`,
  `verifikasi` (`string(255)` NOT NULL tanpa default — sesuai dump),
  `verifikasi_catatan`.
- **000005 `absensi_lokasi`**: `lat`/`lng` `double` → `decimal(10,8)`/
  `decimal(11,8)`; `radius_meter` default 100 → 50; `keterangan`
  `string(255)` → `text`. Tetap 9 kolom.
- **000012 `absensi_setting`** (48 → 89 kolom): ditambah 40 kolom yang
  hilang di §3.1 (denda/point tier & progresif, `face_match_mode`/
  `face_threshold`, `google_*`, `grace_period`, `jam_masuk/pulang_jumat`,
  `max_cuti/izin_hari`, `max_denda_bulan`, `point_*`, `reward_*`,
  `wa_api_key`/`wa_base_url`/`wa_gateway_mode`/`wa_session_name`);
  `jam_masuk`/`jam_pulang` diberi default `'07:00:00'`/`'15:00:00'`;
  `radius_meter` default 100 → 50; `foto_wajib` default 0 → 1;
  `hari_kerja` `json` → `string(255)`; `libur_kalender` `json` →
  `boolean` default 1; `hari_libur_mingguan` `string(50)` → `string(32)`;
  `denda_per_menit`/`denda_pulang_awal` `decimal(15,2)` → `integer`
  default 1000; `wa_gateway_url` kehilangan default tambahan
  `'https://api.fonnte.com/send'` (kini nullable tanpa default, sesuai
  dump); `singleton_guard` dan unique-nya tidak berubah.
- **000051 `user_notifikasi`** (8 → 10 kolom): ditambah `wa_number`
  `string(20)` nullable dan `wa_verified` `boolean` default 0 (§4). Index
  unique `user_id` tetap memakai nama default Laravel
  (`user_notifikasi_user_id_unique`), bukan nama dump `user_id` — lihat
  catatan di §9.4.

### 8.3 Keputusan `master_siswa` — PK komposit + AUTO_INCREMENT kondisional driver

Dump: PK komposit (`id_siswa`, `uid`, `nisn`, `nis`) dengan `AUTO_INCREMENT`
pada `id_siswa`, plus unique terpisah `Id_siswa_idx` (`id_siswa`),
`uid_idx` (`uid`), `nisn` (`nisn`). SQLite melarang `AUTOINCREMENT` pada
PK komposit, jadi migrasi 000059 memakai `autoIncrement()` pada `id_siswa`
**hanya bila driver MySQL**
(`Schema::getConnection()->getDriverName() === 'mysql'`); di SQLite
`id_siswa` ditulis sebagai integer biasa dan PK komposit tetap berlaku.
Terverifikasi: PK komposit terbentuk di kedua engine, dan di MariaDB
`id_siswa` benar-benar `auto_increment` (§9.3, §9.5).

### 8.4 Temuan `kelas_struktur` — `id_kelas` ternyata AUTO_INCREMENT

Spesifikasi awal (§2.16) tidak mencatat AI untuk `kelas_struktur.id_kelas`
karena `CREATE TABLE`-nya tidak memuatnya. Pemeriksaan ulang bagian
`ALTER TABLE` dump membuktikan klaim worker migrasi **benar**:
`ALTER TABLE \`kelas_struktur\` MODIFY \`id_kelas\` int(11) NOT NULL
AUTO_INCREMENT;` (dump baris ±5105). Migrasi 000071 sudah memakai
`increments('id_kelas')`; di MariaDB hasil migrasi, `id_kelas` terverifikasi
`auto_increment` (§9.5). Tidak ada perbaikan yang diperlukan.

### 8.5 Penamaan index kondisional SQLite (11 file)

Nama index di SQLite harus unik se-database, sedangkan dump memakai nama
index generik yang sama di banyak tabel (mis. `idx_sync_uuid`,
`sync_school_uuid`, `idx_deleted_at`, `user_id`). 11 file migrasi memakai
pola kondisional driver: **nama persis dump di MySQL**, nama berawalan nama
tabel (`<tabel>_<nama>_index`) di SQLite:

000053 `users`, 000058 `master_kelas`, 000059 `master_siswa`,
000060 `master_guru`, 000062 `master_mapel`, 000064 `master_jurusan`,
000070 `kelas_siswa`, 000074 `logs`, 000077 `guru_jurnal`,
000090 `piket_kejadian`, 000093 `piket_tamu`.

Contoh: `master_siswa` di SQLite memakai
`master_siswa_idx_sync_uuid_index` (dump: `idx_sync_uuid`), tetapi
`uid_idx` dan `nisn` — yang unik se-database — tetap memakai nama persis
dump di kedua engine. `user_notifikasi` memakai pola yang sama sejak
perbaikan pasca-verifikasi (§9.7): unique-nya bernama `user_id` (sesuai
dump) di MySQL dan `user_notifikasi_user_id_unique` di SQLite — nama dump
`user_id` dipakai juga oleh 4 tabel lain sebagai nama index
(`absensi_saldo_denda`, `face_registration_requests`, `poin_history`,
`reward_redemptions`), sehingga tidak bisa dipakai mentah di SQLite.

## 9. Verifikasi

Verifikasi independen (worker verifikasi, 9 Nov 2026 malam) atas pekerjaan
§8. Metode: `php -l` 49 file; `vendor/bin/pint --test` (baca-saja);
`php artisan migrate:fresh` di SQLite (`.env` apa adanya,
`DB_CONNECTION=sqlite`) dan di MariaDB lokal; perbandingan skema
kolom-per-kolom (nama + urutan + tipe + nullable + default + PK + index)
hasil migrasi vs `CREATE TABLE`/`ALTER TABLE` dump — via PRAGMA SQLite dan
via `information_schema` MariaDB. Tidak ada file migrasi yang diubah oleh
verifikator; tidak ada data baris produksi yang disalin ke mana pun.
Database scratch MariaDB sudah dihapus kembali setelah perbandingan.

### 9.1 `php -l`, Pint, `migrate:fresh`, route, smoke test

| Pemeriksaan | Hasil |
|---|---|
| `php -l` 44 file baru (000053–000096) + 5 file diedit (000002, 000003, 000005, 000012, 000051) | **49/49 lolos, 0 gagal** |
| `vendor/bin/pint --test --format agent database/migrations` | **passed, 0 file** |
| `vendor/bin/pint --test` seluruh repo | **FAIL — 110 file**, 0 di antaranya di `database/migrations`. Semua file yang ditandai adalah kode aplikasi lama (controller/service/library/helper, dsb.) yang tidak disentuh pekerjaan ini — jadi kegagalan Pint repo-wide sudah ada sebelumnya, bukan akibat migrasi ini. Klaim "pint passed" dari worker migrasi benar hanya untuk lingkup migrasi. |
| `php artisan migrate:fresh` SQLite | **EXIT 0; 98/98 migrasi jalan; 102 tabel** (101 + tabel `migrations`) |
| `php artisan migrate:fresh` MariaDB 10.11.14 lokal (koneksi sementara via env, `.env` tidak diubah) | **EXIT 0; 98/98 migrasi jalan; 102 tabel** |
| `php artisan route:list` | **593 route** (sama seperti sebelum pekerjaan ini) |
| Smoke test (`php artisan serve` port 8123) | `/auth` → **200**; `/dashboard` tanpa login → **302 ke /auth**; `/` → 200. Server dimatikan setelah tes. |
| `git status --short` | Hanya `database/migrations/` (5 diubah + 44 baru) dan `PORTING-DB-COMPLETE.md` (baru) yang berubah terkait pekerjaan ini. Perubahan Boost yang belum di-commit (`AGENTS.md`, `CLAUDE.md`, `composer.json`/`composer.lock`, `.agents/`, `.claude/`, `.mcp.json`, `boost.json`) tetap tidak tersentuh. |

Catatan MariaDB: server belum terpasang di mesin ini, jadi dipasang via
`apt-get install -y mariadb-server` (10.11.14 — sejalur dengan 10.11.10
milik dump) khusus untuk verifikasi ini. Kendala yang ditemui dan diatasi:
(1) `bootstrap/cache/config.php` membuat env var inline diabaikan
(migrate pertama malah jalan ke SQLite) — cache di-clear sementara lalu
di-`config:cache` kembali sesudahnya; (2) ekstensi `pdo_mysql` belum ada —
dipasang `php8.3-mysql`; (3) root MariaDB memakai auth `unix_socket`
(TCP ditolak 1698) — migrate dijalankan lewat socket
`/run/mysqld/mysqld.sock`. Setelah selesai, database scratch
`absen_dump`/`absen_laravel` di-drop dan service MariaDB dimatikan.

### 9.2 Perbandingan skema 44 tabel baru + 5 tabel perbaikan

Legenda: **COCOK** = nama+urutan+tipe+nullable+default+PK+index sama di
kedua engine (dengan toleransi SQLite di bawah). **CATATAN** = hanya
perbedaan yang sah/tidak berpengaruh (lihat detail). **TIDAK COCOK
(minor)** = perbedaan nyata tapi kecil (lihat detail); tidak ada tabel
yang kehilangan kolom, salah tipe material, atau kehilangan PK/index.

Toleransi SQLite yang diterapkan (bukan kesalahan): `enum` → `varchar` +
CHECK (nilai enum diverifikasi sama); `char` → `varchar` (panjang
dihilangkan SQLite); `mediumint`/`smallint`/`bigint` → `integer`;
`year` → `integer`; `decimal(p,s)` → `numeric` (presisi tidak disimpan
SQLite — presisi diverifikasi di MariaDB); panjang `varchar` tidak
disimpan SQLite; `ON UPDATE current_timestamp()` tidak didukung SQLite;
kolom generated `singleton_guard` tersembunyi di `PRAGMA table_info`
(88 kolom) tapi ada di `PRAGMA table_xinfo` (89 kolom, `hidden=3`).

| Tabel | Verdict | Detail |
|---|---|---|
| `users` | CATATAN | SQLite: `active` tampil `INTEGER` (artefak: `unsignedTinyInteger` dikompilasi SQLite jadi integer; di MariaDB `tinyint(3) unsigned` — cocok penuh). |
| `groups` | COCOK | |
| `users_groups` | COCOK | |
| `setting` | CATATAN | Hanya lebar display: `enable_ai` dump `int(1)` vs migrasi `int(11)` (tanpa efek di MariaDB 10.11). 50 kolom di kedua sisi. Charset `latin1` dipertahankan. |
| `level_kelas` | COCOK | Tanpa AI di kedua sisi. |
| `master_kelas` | COCOK | FK `key_id_cek` terverifikasi (lihat §9.3). |
| `master_siswa` | COCOK | PK komposit + AI terverifikasi (lihat §9.3). 56 kolom di kedua sisi. |
| `master_guru` | COCOK | 47 kolom di kedua sisi. |
| `level_guru` | COCOK | |
| `master_mapel` | COCOK | 18 kolom di kedua sisi. |
| `master_kelompok_mapel` | COCOK | |
| `master_jurusan` | COCOK | |
| `master_tp` | COCOK | |
| `master_smt` | COCOK | |
| `master_ekstra` | COCOK | |
| `master_hari_efektif` | COCOK | |
| `jabatan_guru` | COCOK | PK varchar tanpa AI; unique `uniq_jabatan_guru_periode` cocok. |
| `kelas_siswa` | COCOK | Tanpa AI; semua unique/key cocok. |
| `kelas_struktur` | COCOK | `id_kelas` AI terverifikasi di dump dan di hasil migrasi (§8.4). |
| `buku_induk` | COCOK | Tanpa AI. |
| `login_attempts` | COCOK | |
| `logs` | COCOK | Key `id_user`/`waktu` cocok. |
| `bulan` | COCOK | |
| `absensi_jadwal_kerja` | COCOK | |
| `guru_jurnal` | COCOK | |
| `guru_jadwal_pribadi` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable (`datetime DEFAULT current_timestamp()`) vs migrasi NOT NULL (`dateTime()->useCurrent()` tanpa `->nullable()`). Default sama; hanya insert NULL eksplisit yang akan ditolak. |
| `kelas_jadwal_mapel` | COCOK | |
| `kelas_jadwal_kbm` | COCOK | |
| `kelas_jadwal_materi` | COCOK | |
| `kelas_materi` | TIDAK COCOK (minor) | `updated_on`: dump nullable vs migrasi NOT NULL (pola `useCurrent()` yang sama). |
| `log_materi` | COCOK | |
| `kelas_catatan_mapel` | COCOK | |
| `kelas_catatan_wali` | COCOK | |
| `kelas_ekstra` | COCOK | PK varchar tanpa AI. |
| `penilaian_guru_periode` | CATATAN | Hanya lebar display: `id` dump `int(11) unsigned` vs migrasi `int(10) unsigned` (`increments()`). |
| `penilaian_guru_skor` | CATATAN | Sama: `id` `int(11)` vs `int(10)` unsigned. |
| `piket_jadwal` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable vs migrasi NOT NULL. |
| `piket_kejadian` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable vs migrasi NOT NULL. |
| `piket_absensi` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable vs migrasi NOT NULL. |
| `piket_presensi_guru` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable vs migrasi NOT NULL. |
| `piket_tamu` | TIDAK COCOK (minor) | `created_at`/`updated_at`: dump nullable vs migrasi NOT NULL. |
| `post` | COCOK | `updated` tanpa ON UPDATE cocok. |
| `post_comments` | COCOK | |
| `post_reply` | COCOK | |
| `absensi_harian` | TIDAK COCOK (minor) | 5 kolom dump nullable vs migrasi NOT NULL: `telat_menit`, `datang_awal_menit`, `pulang_cepat_menit`, `pulang_akhir_menit` (`integer()->default(0)` tanpa `->nullable()`), `is_mock_location` (`boolean()->default(0)`). Default sama (0). Plus lebar display `id_guru`/`izin_id` `int(11)` vs `int(10)` unsigned (catatan). Selain itu 41 kolom, semua tipe/default/PK/index cocok — termasuk `denda decimal(15,2)` dan koordinat `decimal(10,8)/(11,8)`. |
| `absensi_izin` | CATATAN | Hanya lebar display: `id_guru` `int(11)` vs `int(10)` unsigned. 17 kolom, `verifikasi` NOT NULL tanpa default, dan `status` `string(20)` cocok. |
| `absensi_lokasi` | TIDAK COCOK (minor) | `radius_meter`: dump nullable `DEFAULT 50` vs migrasi NOT NULL `DEFAULT 50`. Selain itu cocok (koordinat `decimal(10,8)/(11,8)`, `keterangan` text). |
| `absensi_setting` | CATATAN | Hanya lebar display: `mikrotik_port` `int(5)` vs `int(11)`, `wfh_max_per_bulan` `int(3)` vs `int(11)`. 89 kolom di kedua sisi; `singleton_guard` generated cocok (lihat §9.3). Default `wa_gateway_url` yang tadinya salah sudah hilang. |
| `user_notifikasi` | TIDAK COCOK (minor) | Nama unique index: dump `user_id` vs migrasi `user_notifikasi_user_id_unique` (nama default Laravel). Di SQLite nama dump tidak bisa dipakai apa adanya karena 4 tabel lain juga memakai `user_id` sebagai nama index; di MySQL nama itu sebenarnya bisa dipakai karena nama index berlaku per tabel. Plus lebar display `id`/`user_id` `int(11)` vs `int(10)` unsigned. Kolom `wa_number` + `wa_verified` ada di kedua sisi. |

**Rekap: 33 COCOK penuh · 6 CATATAN (display width / artefak SQLite,
tanpa efek) · 10 TIDAK COCOK (minor)** — 9 tabel soal nullability
(`guru_jadwal_pribadi`, `kelas_materi`, 5 tabel piket, `absensi_harian`,
`absensi_lokasi`) + 1 tabel soal nama index (`user_notifikasi`).
Di MariaDB (perbandingan `information_schema` langsung): 34/49 cocok
penuh; semua tipe material cocok — `enum` (nilai CHECK sama),
`decimal` (presisi sama, mis. `denda decimal(15,2)`),
`char(30)` `master_guru.nip`, `year` tidak ada di 49 tabel ini,
`tinyint(3) unsigned` `users.active`, `ON UPDATE current_timestamp()`
(sama di kedua sisi), kolom generated, PK, dan semua index kecuali
`user_notifikasi` di atas. Di SQLite: 37/49 cocok penuh; sisanya
perbedaan yang sama (nullability) plus 3 artefak SQLite (`users`,
`absensi_setting`, `user_notifikasi`).

### 9.3 Verifikasi khusus

- **(a) `master_siswa` PK komposit** — terbentuk di kedua engine:
  SQLite `PRAGMA table_info` menunjukkan `pk` 1–4 untuk
  (`id_siswa`, `uid`, `nisn`, `nis`) sesuai urutan dump; MariaDB
  `PRIMARY KEY (id_siswa, uid, nisn, nis)` sama persis. Unique
  `Id_siswa_idx`, `uid_idx`, `nisn` juga ada di kedua engine.
- **(b) FK `master_kelas.level_id` → `level_kelas.id_level`** — terbentuk
  di kedua engine: SQLite `PRAGMA foreign_key_list('master_kelas')`
  mengembalikan `level_kelas/id_level` (NO ACTION/NO ACTION); MariaDB
  `information_schema` menunjukkan constraint **`key_id_cek`** —
  nama constraint sama dengan dump.
- **(c) `absensi_setting` 89 kolom** — SQLite `PRAGMA table_info` = 88
  (menyembunyikan kolom generated), `PRAGMA table_xinfo` = 89; kolom
  ke-89 adalah `singleton_guard` (`hidden=3`, `integer as (1)` di SQLite;
  di MariaDB `tinyint(4) GENERATED ALWAYS AS (1) STORED` + unique
  `uq_absensi_setting_single_row` — identik dengan dump).
- **(d) `user_notifikasi` punya `wa_number` + `wa_verified`** — ada di
  kedua engine (`wa_number varchar(20)` nullable,
  `wa_verified tinyint(1)` default 0). 10 kolom di kedua sisi.
- **(e) `kelas_struktur.id_kelas` AUTO_INCREMENT** — klaim worker migrasi
  **TERKONFIRMASI**: dump `ALTER TABLE … MODIFY \`id_kelas\` int(11) NOT
  NULL AUTO_INCREMENT`, dan hasil migrasi di MariaDB menunjukkan
  `id_kelas … extra: auto_increment`. Migrasi 000071 sudah benar sejak
  awal; tidak ada yang perlu diperbaiki.

### 9.4 Koreksi atas dokumen ini (§2)

Jumlah kolom di header §2 ternyata salah hitung pada 4 tabel (daftar
kolomnya sendiri benar): `master_siswa` tertulis 57, sebenarnya **56**;
`master_guru` tertulis 48, sebenarnya **47**; `master_mapel` tertulis 19,
sebenarnya **18**; `setting` tertulis 49, sebenarnya **50**. Migrasi
mengikuti jumlah yang benar (56/47/18/50) dan cocok dengan dump.
Header §2 tersebut sebaiknya dikoreksi pada revisi dokumen berikutnya;
bukan kesalahan migrasi.

### 9.5 Charset

Semua migrasi memakai `utf8mb4_general_ci` kecuali `setting` (`latin1`,
sesuai dump). Di MariaDB hasil migrasi: 101 dari 102 tabel
`utf8mb4_general_ci` (termasuk `migrations`), 1 tabel `latin1_swedish_ci`
(`setting`). Dump memakai `utf8mb3` untuk 56 tabel — perbedaan charset
ini sudah dicatat sebagai penyimpangan sadar di §7.8 dan bukan temuan baru.

### 9.6 Yang masih menggantung

1. **10 perbedaan minor di §9.2** (9 tabel nullability + 1 nama index).
   Perbaikan yang disarankan (bukan dilakukan verifikator): tambahkan
   `->nullable()` pada kolom-kolom `useCurrent()`/`default(0)`/
   `default(50)` yang dump-nya nullable — `guru_jadwal_pribadi.created_at/
   updated_at`, `kelas_materi.updated_on`, `created_at/updated_at` 5 tabel
   piket, `absensi_harian.telat_menit/datang_awal_menit/pulang_cepat_menit/
   pulang_akhir_menit/is_mock_location`, `absensi_lokasi.radius_meter` —
   dan beri nama eksplisit `->unique('user_id', 'user_id')` di
   `user_notifikasi` bila nama dump diinginkan (aman di MySQL; di SQLite
   perlu pola kondisional seperti §8.5 karena nama `user_id` dipakai 4
   tabel lain). Dampak bila dibiarkan: hanya insert NULL eksplisit pada
   kolom-kolom itu yang akan ditolak; perilaku default dan pembacaan
   aplikasi tidak berubah.
2. **Pint repo-wide FAIL (110 file)** — sudah ada sebelum pekerjaan ini,
   di luar lingkup migrasi; perbaikan formatting repo-wide adalah
   keputusan terpisah.
3. **Push ke GitHub dan skema 12 tabel inti** — tetap tertunda dari
   pekerjaan sebelumnya (di luar cakupan verifikasi ini).
4. MariaDB lokal yang dipasang untuk verifikasi ini sudah dimatikan dan
   database scratch sudah dihapus; paket `mariadb-server` dan ekstensi
   `php8.3-mysql` tetap terpasang di VM (dapat dipakai lagi bila
   verifikasi MySQL diperlukan di lain waktu).

### 9.7 Perbaikan pasca-verifikasi (koordinator, 9 Nov 2026 malam)

Ke-10 perbedaan minor §9.2 **diperbaiki** (bukan dibiarkan sebagai deviasi):

- `->nullable()` ditambahkan pada 16 kolom di 9 tabel, sesuai dump (nullable
  dengan default): `absensi_harian.telat_menit/datang_awal_menit/
  pulang_cepat_menit/pulang_akhir_menit/is_mock_location`,
  `absensi_lokasi.radius_meter`, `guru_jadwal_pribadi.created_at/updated_at`,
  `kelas_materi.updated_on`, `created_at/updated_at` di 5 tabel piket
  (`piket_jadwal`, `piket_kejadian`, `piket_absensi`, `piket_presensi_guru`,
  `piket_tamu`). Default tidak berubah (0 / 50 / `CURRENT_TIMESTAMP`).
- `user_notifikasi`: unique kini memakai pola kondisional driver seperti
  §8.5 — nama `user_id` (sesuai dump) di MySQL,
  `user_notifikasi_user_id_unique` di SQLite. §8.5 sudah dikoreksi
  (sebelumnya menyatakan pengecualian untuk tabel ini).
- Header jumlah kolom §2 juga dikoreksi (§9.4): `master_siswa` 57→**56**,
  `master_guru` 48→**47**, `master_mapel` 19→**18**, `setting` 49→**50**.

Verifikasi ulang setelah perbaikan (koordinator):
- `php -l` 10/10 file yang diubah lolos; `vendor/bin/pint --dirty`
  passed (lingkup `database/migrations`).
- `migrate:fresh` SQLite: EXIT 0, 98/98 migrasi. PRAGMA mengonfirmasi
  ke-16 kolom kini `notnull=0` dengan default utuh, dan index SQLite
  `user_notifikasi` bernama `user_notifikasi_user_id_unique` (sesuai pola
  kondisional). Jumlah tabel SQLite 103 = 101 tabel aplikasi/infra +
  `migrations` + `sqlite_sequence` (tabel internal SQLite untuk
  AUTOINCREMENT; bukan tabel aplikasi — angka "102" di §9.1 menghitung
  tanpa `sqlite_sequence`).
- `migrate:fresh` MariaDB 10.11.14 lokal: EXIT 0, 98/98 migrasi, **102
  tabel** (101 + `migrations`). `information_schema` mengonfirmasi ke-16
  kolom `IS_NULLABLE=YES` dengan default utuh; unique `user_notifikasi`
  bernama **`user_id`** (sesuai dump); FK `key_id_cek` tetap ada. Database
  scratch di-drop dan service MariaDB dimatikan kembali; `.env` tidak
  diubah; config cache dikembalikan (`config:cache`).

Dengan perbaikan ini, **49/49 tabel cocok penuh** dengan dump (dalam
toleransi SQLite §9.2 dan penyimpangan sadar charset §7.8). Sisa "CATATAN"
di §9.2 hanyalah lebar display int (tanpa efek di MariaDB 10.11) dan
artefak tampilan SQLite.

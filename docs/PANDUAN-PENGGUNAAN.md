# Panduan Penggunaan Aplikasi Presensi & Jurnal Mengajar

Panduan ini ditujukan untuk Admin/Tata Usaha, Guru, Guru Piket, Kepala Sekolah, dan Siswa.
Isinya mencakup pengisian data awal oleh admin, presensi guru dan siswa, pengisian jurnal
mengajar, validasi jurnal, sampai laporan rekap.

> Versi panduan: November 2026. Nama menu mengikuti tampilan aplikasi. Jika tampilan berbeda,
> pastikan aplikasi sudah diperbarui.

---

## Daftar Isi

1. [Gambaran Umum dan Peran Pengguna](#1-gambaran-umum-dan-peran-pengguna)
2. [Persiapan Perangkat](#2-persiapan-perangkat)
3. [Login dan Navigasi](#3-login-dan-navigasi)
4. [Setup Data oleh Admin](#4-setup-data-oleh-admin)
5. [Presensi Guru](#5-presensi-guru)
6. [Presensi Siswa](#6-presensi-siswa)
7. [Jurnal Mengajar Guru](#7-jurnal-mengajar-guru)
8. [Validasi Jurnal oleh Guru Piket / Kepala Sekolah](#8-validasi-jurnal-oleh-guru-piket--kepala-sekolah)
9. [Monitoring dan Laporan Admin](#9-monitoring-dan-laporan-admin)
10. [Tugas Kepala Sekolah](#10-tugas-kepala-sekolah)
11. [Alur Harian Ringkas](#11-alur-harian-ringkas)
12. [F.A.Q. dan Penanganan Masalah](#12-faq-dan-penanganan-masalah)
13. [Lampiran: Checklist Setup dan Daftar Status](#13-lampiran-checklist-setup-dan-daftar-status)

---

## 1. Gambaran Umum dan Peran Pengguna

Aplikasi ini menangani tiga hal utama:

1. **Presensi guru** — masuk/pulang dengan Face ID, QR acara, RFID/NFC, atau input manual.
2. **Presensi siswa** — kiosk RFID/NFC, scanner QR (kamera, scanner gun, atau NIS), dan input per rombel.
3. **Jurnal mengajar guru** — diisi per sesi/jam pelajaran, lalu divalidasi oleh guru piket atau kepala sekolah.

| Peran | Login sebagai | Menu utama |
|---|---|---|
| Admin / Tata Usaha | role admin | Data master, pengaturan, monitoring, persetujuan, rekap |
| Guru | role guru | Presensi, jurnal, jadwal, presensi siswa, piket, lembur/izin |
| Guru Piket | role guru + jadwal piket | Panel Piket, validasi jurnal, buku tamu, kejadian |
| Kepala Sekolah | akun guru yang ditandai sebagai kepala sekolah (tugas tambahan/jabatan) | Menu monitoring jurnal tambahan |
| Siswa | role siswa | Presensi Saya (riwayat dan kartu digital) |

**Kaitan penting antara jurnal dan presensi pulang:** secara bawaan guru wajib mengisi jurnal
untuk semua sesi hari itu sebelum bisa absen pulang. Jika admin mengaktifkan *mode ketat*,
jurnal tersebut juga harus sudah **disetujui** guru piket/kepala sekolah.

---

## 2. Persiapan Perangkat

| Keperluan | Ketentuan |
|---|---|
| Browser | Chrome/Edge versi terbaru. Safari/Firefox untuk fitur kamera dan NFC tidak dijamin. |
| HTTPS | Wajib untuk kamera (Face ID, scan QR) dan Web NFC. Alamat `http://` biasa akan ditolak browser. |
| Izin browser | Izinkan **Kamera**, **Lokasi**, dan (bila memakai NFC) **NFC** saat diminta. |
| Kiosk RFID/NFC | Tablet/HP Android + Chrome + HTTPS. Perangkat tanpa kamera tetap bisa memakai RFID. |
| Scanner QR desktop | PC dengan webcam, atau scanner gun/USB yang bekerja sebagai keyboard. |
| Lokasi | GPS aktif dan akurasi lokasi baik. Presensi di luar radius lokasi kantor akan ditolak. |

Tips kiosk: pasang tablet di meja piket, aktifkan mode kiosk browser (kunci layar penuh),
dan biarkan halaman kiosk terbuka selama jam operasional.

---

## 3. Login dan Navigasi

### 3.1 Login

1. Buka alamat aplikasi, lalu masuk ke halaman login (`/auth`).
2. Isi **Username** dan **Password**.
3. Klik **Masuk**.

Setelah login, pengguna diarahkan otomatis sesuai peran:

| Peran | Halaman awal |
|---|---|
| Siswa | Presensi Saya (`/siswa/presensi`) |
| Guru | Presensi harian (`/presensi`) |
| Admin | Dashboard (`/dashboard`) |

### 3.2 Password awal dan reset

| Kondisi | Password |
|---|---|
| Import guru tanpa mengisi kolom Password | `123456` |
| Import siswa tanpa mengisi kolom Password | NIS siswa |
| Reset oleh admin (Manajemen Pengguna → Guru) | `123456` |
| Reset oleh admin (Manajemen Pengguna → Siswa) | NISN siswa |

Minta pengguna segera mengganti password setelah login pertama.

### 3.3 Batas percobaan login

- **3 kali gagal** untuk username yang sama dalam 5 menit → username terkunci sementara.
- **30 kali gagal** dari satu alamat IP dalam 5 menit → jaringan tersebut diblokir sementara.

Tunggu 5 menit, atau minta admin mereset percobaan login dari **Manajemen Pengguna**.

### 3.4 Navigasi

Menu berada di sidebar kiri dan dikelompokkan per tugas (Utama, Presensi & KBM, Persetujuan,
Laporan, Data Master, Pengaturan). Di layar HP, sidebar dapat dibuka lewat tombol menu.
Tombol **Keluar** berada di bagian bawah sidebar / menu profil.

---

## 4. Setup Data oleh Admin

Lakukan setup berurutan. Banyak fitur bergantung pada data sebelumnya (contoh: jurnal butuh
jadwal dan mapping kelas–mapel; presensi butuh kelas dan lokasi).

### 4.1 Tahun Pelajaran dan Semester

Menu: **Data Master → Data Akademik → Tahun Pelajaran** (`/datatahun`)

1. Klik **Tambah Periode**, isi nama tahun pelajaran (mis. `2026/2027`) dan tanggal mulai/selesai.
2. Tambahkan semester pada periode tersebut (Ganjil/Genap beserta tanggal).
3. Klik **Aktifkan** pada tahun pelajaran dan semester yang sedang berjalan.
   Hanya satu periode dan satu semester yang aktif.
4. Isi **Hari Efektif Semester Ini** dan simpan. Angka ini dipakai pada rekap.

Tanpa periode/semester aktif, jadwal, jurnal, dan rekap tidak dapat diproses dengan benar.

### 4.2 Jurusan (opsional, untuk SMK)

Menu: **Data Master → Data Akademik → Jurusan** (`/datajurusan`)

1. Klik **Tambah Jurusan**.
2. Isi nama dan kode jurusan, lalu simpan.
3. Jurusan dipakai saat membuat kelas/rombel.

### 4.3 Kelas / Rombel dan Wali Kelas

Menu: **Data Master → Data Akademik → Kelas / Rombel** (`/datakelas`)

**Tambah satu kelas:**

1. Klik **Tambah Kelas**.
2. Isi nama kelas, tingkat, jurusan (bila ada), dan wali kelas.
3. Simpan.

**Tambah banyak kelas sekaligus:**

1. Klik **Tambah Sekaligus**.
2. Isi format tingkat, level, suffix (mis. `A`, `B`), jumlah kelas, dan mulai dari.
3. Simpan. Periksa daftar kelas yang terbentuk.

Wali kelas dapat diubah kapan saja. Wali kelas memengaruhi kelas yang dapat dikelola guru
pada presensi siswa dan jurnal.

### 4.4 Mata Pelajaran

Menu: **Data Master → Data Akademik → Mata Pelajaran** (`/datamapel`)

1. Klik **Tambah Mapel**.
2. Isi nama mapel, kode mapel, urutan tampil, kelompok (umum/kejuruan), dan status aktif.
3. Simpan. Mapel bawaan sistem tidak dapat dihapus; nonaktifkan bila tidak dipakai.

Jika ada kesalahan, gunakan tombol edit. Hapus massal tersedia lewat centang pada daftar.

### 4.5 Data Guru dan GTK

Menu: **Data Master → Data Guru & Siswa → Data Guru & GTK** (`/dataguru`)

**Tambah manual:**

1. Klik **Tambah Guru**.
2. Isi nama, NIP (angka), kode guru, Telegram ID, nomor WhatsApp, username, dan password.
3. Simpan.

**Import Excel (maksimal 1.000 baris):**

1. Klik **Import Excel**, lalu **Download Format**.
2. Isi kolom: `No, Nama, NIP, Kode Guru, Username, Password`.
3. Ketentuan: NIP harus angka; username 4–30 karakter; password minimal 5 karakter.
   Password kosong otomatis menjadi `123456`.
4. Unggah file dan periksa hasil import.

**Daftarkan kartu RFID/NFC:**

1. Pada baris guru, buka menu **kartu RFID/NFC**.
2. Pilih cara: **Web NFC** (Chrome Android), **USB reader**, atau **ketik UID manual**.
3. Tempelkan kartu, lalu simpan. UID tidak boleh dipakai dua orang.

**Atur jabatan dan mapping kelas–mapel (penting):**

1. Pada baris guru, klik **Edit Jabatan**.
2. Isi **Jabatan/level**, centang **Wali Kelas** bila berlaku, pilih **mapel** dan **ekstrakurikuler**.
3. Tentukan **Mapping Kelas × Mapel** (pasangan kelas dan mapel yang diajar guru).
4. Simpan.

Mapping ini menentukan pilihan kelas/mapel pada form jurnal dan membatasi akses guru
ke presensi kelas yang bukan tanggung jawabnya.

### 4.6 Data Siswa

Menu: **Data Master → Data Guru & Siswa → Data Siswa** (`/datasiswa`)

**Tambah manual:**

1. Klik **Tambah Siswa**.
2. Isi NIS (angka), NISN, nama, jenis kelamin, kelas awal, dan password (opsional).
3. Simpan. Username dan password default mengikuti NIS.

**Import Excel (maksimal 2.000 baris):**

1. Klik **Import**, lalu unduh format.
2. Isi kolom: `No, NISN, NIS, Nama, JK, Username, Password, Kelas Awal, Tahun Masuk`.
3. Ketentuan: NIS harus angka; username/password kosong otomatis memakai NIS.
4. Unggah file, lalu periksa jumlah siswa per kelas.

**Daftarkan kartu RFID/NFC:** sama seperti guru, lewat modal kartu pada daftar siswa.
Kartu ini dipakai untuk absensi di kiosk dan dapat juga dipakai pada scan QR siswa
(UID dibaca sebagai alternatif NIS).

**Filter dan status:** gunakan filter kelas/status untuk memeriksa data. Siswa nonaktif
tidak dapat melakukan presensi dan akan ditolak saat login.

### 4.7 Jadwal Pelajaran

Menu: **Data Master → Data Akademik → Jadwal Pelajaran** (`/admin/jadwal`)

**Atur jam KBM:**

1. Buka **Pengaturan Jadwal**.
2. Tentukan hari libur mingguan, jam masuk dan pulang KBM, format otomatis (durasi mapel),
   hari dan jam upacara, jumlah mapel per hari, serta jumlah istirahat.
3. Simpan.

**Isi jadwal per slot:**

1. Pada grid hari, klik **Atur Mapel** di slot yang diinginkan.
2. Pilih kelas, mapel, dan guru pengampu.
3. Simpan. Ulangi untuk seluruh slot.

**Mempercepat pengisian:**

- **Copy Jadwal** — menyalin jadwal dari hari/kelas lain.
- **Template Excel** dan **Import Excel** — mengisi jadwal secara massal.
- **Format Otomatis** — menyusun ulang jam pelajaran sesuai durasi mapel.

Jadwal yang benar adalah syarat agar jurnal menampilkan "Jadwal Hari Ini" dan guru piket
melihat slot yang belum dijurnal.

### 4.8 Jadwal Piket Guru

Menu: **Presensi & KBM → Piket Guru → Jadwal Petugas Piket** (`/admin/piket`)

1. Lihat **Petugas Piket Hari Ini** dan jadwal mingguan.
2. Klik **Generate Otomatis** untuk membuat jadwal.
3. Isi jumlah petugas per hari, pilih hari, dan pilih mode **replace** (ganti) atau **append** (tambah).
4. Simpan. Petugas dapat ditambah/diubah manual bila perlu.

Guru piket bertugas mengisi presensi kehadiran guru per sesi dan **memvalidasi jurnal**.
Tanpa jadwal piket, validasi jurnal tidak bisa dilakukan (aplikasi menampilkan peringatan
"Belum ada guru piket terjadwal hari ini").

### 4.9 Shift Kerja

Menu: **Presensi & KBM → Presensi Guru → Shift Kerja** (`/admin/presensi/shift`)

1. Klik **Tambah Shift**, isi nama shift, jam masuk, jam pulang, dan grace period (menit).
2. Simpan.
3. Gunakan **Atur Guru × Hari** untuk menetapkan guru ke shift pada hari tertentu.
4. Aktifkan/nonaktifkan shift lewat tombol toggle.

Guru yang tidak memiliki shift memakai jam kerja global pada Pengaturan Presensi.
Jam pulang shift harus lebih besar dari jam masuk.

### 4.10 Lokasi Kantor (Geofence)

Menu: **Pengaturan → Pengaturan Umum → Lokasi Kantor** (`/admin/presensi/lokasi`)

1. Klik **Tambah Lokasi**.
2. Cari alamat atau gunakan tombol **Lokasi Saya**.
3. Geser penanda ke titik pusat sekolah.
4. Atur **radius** (preset 25/50/100/200/500 m atau slider).
5. Centang **Lokasi Utama** untuk lokasi default, lalu simpan.

Presensi di luar radius akan ditolak dengan pesan "Di luar area lokasi absensi".
Pastikan radius mencakup area gerbang dan ruang guru, tetapi tidak terlalu lebar.

### 4.11 Pengaturan Presensi

Menu: **Pengaturan → Pengaturan Umum → Pengaturan Aplikasi** (`/settings`),
lalu buka tab **Presensi**.

| Tab | Isi penting |
|---|---|
| **Profil** | Identitas sekolah, NPSN, alamat, kontak, kepala sekolah, format surat teguran. |
| **Jadwal** | Jadwal kerja mingguan GTK (jam masuk/pulang, status libur), grace period, radius meter. |
| **Wajah & Kamera** | Mode pencocokan wajah (`ai_match` atau `selfie_only`), threshold, foto wajib, verifikasi wajah saat WFH. |
| **Poin & Denda** | Poin kehadiran, poin pelanggaran per menit, poin lembur, denda bertingkat. |
| **Batasan & WFH** | Kuota izin/cuti/sakit, kuota WFH per bulan, batas minimum kehadiran, syarat jurnal sebelum pulang. |
| **Notifikasi** | Bot Telegram, WhatsApp, channel per jenis notifikasi. |
| **Tampilan** | Tema dan tampilan aplikasi. |
| **Maintenance** | **Token Kiosk** untuk membuka kiosk publik. |

**Pengaturan yang paling memengaruhi operasional harian:**

- **Jam kerja mingguan dan grace period** — menentukan status terlambat.
- **Radius** — batas jarak presensi.
- **Foto wajib** — mewajibkan selfie saat absen.
- **Kuota WFH/bulan** (default 5) dan jam WFH.
- **Wajib jurnal sebelum pulang** (default aktif) — guru harus mengisi jurnal semua sesi hari itu.
- **Wajib jurnal tervalidasi sebelum pulang** (mode ketat, default nonaktif) — jurnal juga harus
  sudah disetujui piket. Opsi ini hanya berlaku bila opsi di atas aktif.
- **Token Kiosk** — isi token acak, lalu buka kiosk dengan `/kiosk?k=TOKEN` sekali per perangkat.

### 4.12 Manajemen Pengguna

Menu: **Pengaturan → Manajemen Pengguna** (`/admin/users`)

1. Tab **Admin**: tambah admin baru (nama, username, email, password minimal 6 karakter).
2. Tab **Guru** / **Siswa**: aktifkan/nonaktifkan akun, reset password, dan reset percobaan login.
3. Perubahan berlaku langsung pada login berikutnya.

### 4.13 Integrasi Dapodik (opsional)

Menu: **Data Master → Integrasi Dapodik** (`/integrasi/dapodik`)

Gunakan hanya jika sekolah menyiapkan berkas ekspor Dapodik. Ikuti petunjuk pada halaman
dan periksa hasil mapping sebelum menyimpan, agar data yang sudah ada tidak tertimpa.

---

## 5. Presensi Guru

### 5.1 Halaman Presensi Harian

Setelah login, guru diarahkan ke halaman presensi (`/presensi`). Halaman ini memuat tombol:

- **Absen** (Face ID / selfie)
- **Scan QR** (QR acara)
- **Izin / Sakit**
- **Koreksi**
- **Riwayat**
- **Lembur**
- **Kartu QR**
- **Rekap PDF**

Menu **Presensi Saya** di sidebar membuka riwayat presensi bulanan (`/guru/absensi`),
lengkap dengan filter bulan/tahun, jam masuk/pulang, detail per hari, dan ekspor Excel.

### 5.2 Absen Masuk dengan Face ID

1. Buka halaman presensi, lalu tekan **Absen**.
2. Izinkan kamera dan lokasi.
3. Pastikan wajah berada di dalam bingkai dan pencahayaan cukup.
4. Tekan tombol absen, tunggu proses verifikasi.
5. Bila berhasil, muncul konfirmasi jam masuk dan status kehadiran.

Syarat yang diperiksa sistem:

- Berada di dalam radius lokasi kantor (kecuali Mode Dinas).
- Wajah cocok dengan data wajah yang sudah disetujui (sesuai mode kamera).
- Foto tersimpan bila **foto wajib** diaktifkan.
- Tidak terdeteksi lokasi palsu (*mock location*).
- Belum pernah absen masuk pada hari yang sama.

### 5.3 Mode Dinas dan WFH

Pada modal Face ID, buka panel **Opsi dinas / WFH**:

**Mode Dinas** — untuk tugas luar (rapat, kunjungan, lomba):

1. Pilih **Dinas**.
2. Isi keterangan/alasan tugas.
3. Lanjutkan absen. Radius tidak diberlakukan; dinas dihitung hadir dan tidak memakai kuota.

**WFH** — bekerja dari rumah:

1. Pilih **WFH**.
2. Isi keterangan.
3. Lanjutkan absen pada jam WFH yang diatur.

Ketentuan: fitur WFH harus diaktifkan admin, kuota bulanan masih tersedia, dan tidak bisa
digabung dengan Mode Dinas. Bila kuota habis, ajukan izin/cuti atau absen di kantor.

### 5.4 Absen Masuk dengan QR Acara

Dipakai saat sekolah membuat QR acara (mis. upacara, seminar):

1. Admin membuat QR di **Presensi & KBM → Presensi Guru → QR Event / Acara**.
2. Guru membuka **Scan QR** di halaman presensi.
3. Pindai QR yang ditampilkan/dicetak admin.
4. Sistem mencatat presensi sesuai waktu dan mode QR (statis atau berputar).

QR statis dapat dicetak dan ditempel; QR berputar menghasilkan token yang berubah setiap
beberapa detik sehingga lebih sulit disalahgunakan.

### 5.5 Izin, Sakit, dan Cuti

1. Di halaman presensi, tekan **Izin / Sakit**.
2. Pilih jenis (izin, sakit, cuti), tanggal mulai/selesai, dan isi alasan.
3. Kirim pengajuan.
4. Admin menyetujui di **Persetujuan & Verifikasi → Pengajuan Guru → Izin / Cuti**.

Setelah disetujui, hari tersebut otomatis tercatat dan guru tidak perlu absen manual.
Perhatikan kuota tahunan dan batas durasi per pengajuan (diatur admin).

### 5.6 Koreksi Presensi

Dipakai bila lupa absen masuk/pulang atau jam tercatat salah:

1. Tekan **Koreksi** di halaman presensi.
2. Pilih tanggal, jenis koreksi (masuk/pulang), dan jam yang benar.
3. Isi alasan, lalu kirim.
4. Admin meninjau di **Persetujuan & Verifikasi → Koreksi & Verifikasi → Koreksi Absen**.

### 5.7 Absen Pulang dan Syarat Jurnal

1. Buka halaman presensi, tekan **Absen** (atau tombol pulang yang tersedia).
2. Sistem memeriksa:
   - Guru sudah absen masuk hari itu.
   - Belum absen pulang.
   - Jam pulang sesuai jam kerja/shift; pengecualian hanya untuk Mode Dinas, WFH, atau
     kebijakan izin pulang lebih awal yang diaktifkan sekolah.
   - Berada di radius lokasi.
   - **Semua sesi mengajar hari itu sudah memiliki jurnal** (bila wajib jurnal aktif).
   - **Jurnal sudah disetujui piket** (bila mode ketat aktif).
3. Bila syarat belum terpenuhi, aplikasi menampilkan pesan dan tautan untuk mengisi jurnal.

Jika jurnal belum lengkap, buka **Jurnal Mengajar**, isi jurnal sesi yang kurang, lalu
ulangi absen pulang. Pada mode ketat, tunggu guru piket menyetujui jurnal terlebih dahulu.

### 5.8 Profil, Wajah, dan Kartu

- **Profil Saya** (`/presensi/profile`): ubah nama, email, jabatan, Telegram, WhatsApp,
  username/password, unggah foto, dan **Daftarkan/Perbarui Wajah**.
- Pendaftaran wajah berstatus menunggu persetujuan admin. Setujui di
  **Persetujuan & Verifikasi → Pengajuan Wajah**.
- **Kartu QR Saya** (`/guru/kartu-saya`): kartu identitas dengan QR untuk scan di kiosk.
  Dapat dicetak sendiri.
- **Lembur** (`/guru/lembur`): ajukan lembur beserta tanggal dan jam; admin menyetujui
  di **Persetujuan & Verifikasi → Lembur**.
- **Surat teguran:** bila ada surat teguran aktif, presensi masuk akan meminta konfirmasi
  pembacaan terlebih dahulu sebelum absen dapat dilanjutkan.

---

## 6. Presensi Siswa

Tersedia beberapa cara. Pilih sesuai perangkat dan kebiasaan sekolah.

### 6.1 Kiosk RFID / NFC

Menu: **Presensi & KBM → Presensi Siswa → Kiosk RFID / NFC** (`/kiosk`)

1. Admin mengisi **Token Kiosk** di Pengaturan → Maintenance.
2. Buka `/kiosk?k=TOKEN` pada tablet Android (Chrome, HTTPS). Token hanya dipakai sekali
   untuk mendaftarkan perangkat; setelah itu halaman dapat dibuka tanpa token.
3. Pilih mode **Auto**, **Masuk**, atau **Pulang**.
4. Aktifkan suara bila diinginkan, lalu kunci GPS.
5. Siswa/guru menempelkan kartu ke perangkat. Sistem menampilkan nama dan status.

Catatan:

- Web NFC hanya berjalan di Chrome Android dengan HTTPS.
- Kiosk publik sengaja terkunci bila token belum diisi.
- Mode Auto mengikuti jadwal/waktu server untuk menentukan masuk atau pulang.
- Jika lokasi belum terkunci, kiosk menolak pemindaian.

### 6.2 Scanner QR Desktop

Menu: **Presensi & KBM → Presensi Siswa → Scanner QR** (`/scanner`)

1. Buka halaman scanner (perangkat kiosk perlu token `?k=TOKEN`, atau login sebagai admin/guru).
2. Pilih kamera, lalu arahkan QR kartu siswa ke kamera.
3. Pilih mode **Masuk** atau **Pulang**. Mode mengikuti jendela waktu scan yang diatur admin.
4. Alternatif tanpa kamera:
   - **Scanner gun/USB** — arahkan tembakan ke kolom input.
   - **Input NIS manual** — ketik NIS lalu Enter.
5. Gunakan filter kelas untuk membatasi daftar.
6. Riwayat scan hari ini tampil di panel samping.

Jendela waktu scan (mulai/selesai) diatur pada pengaturan presensi. Di luar jendela,
scan masuk/pulang dapat ditolak.

### 6.3 Scan QR Siswa oleh Guru (HP)

Menu: **KBM & Jurnal → Presensi Siswa → Scan QR Siswa** (`/guru/presensi-siswa`)

1. Buka halaman scan (tautan sidebar sudah membawa token akses).
2. Pilih **Absen Masuk** atau **Absen Pulang**.
3. Pindai QR pada kartu siswa.
4. Bila kamera bermasalah, gunakan tombol input manual dan masukkan **NIS, isi QR, atau UID RFID**.
5. Hasil scan muncul sebagai popup (nama, kelas, status, jam).
6. Daftar scan hari ini tersedia, termasuk filter **Belum Pulang**.

Halaman ini khusus guru; siswa tidak dapat membuka presensi kelas.

### 6.4 Input Presensi Rombel (Manual per Kelas)

Menu: **Presensi & KBM → Presensi Siswa → Input Presensi Rombel** (`/presensisiswa`)

1. Pilih tanggal dan kelas, lalu muat daftar siswa.
2. Tentukan status setiap siswa: **HADIR, SAKIT, IZIN, ALPA, BDR**, atau **TANPA KETERANGAN (-/-)**.
3. Gunakan **Semua Hadir** atau **Semua BDR** untuk mengisi cepat, lalu sesuaikan.
4. Isi jam masuk/keluar bila diperlukan.
5. Klik **Simpan Absensi** dan konfirmasi.

Peringatan penting: menyimpan ulang dapat **menimpa** data presensi kelas pada tanggal itu.
Periksa status yang sudah ada sebelum menyimpan. Guru non-piket hanya dapat mengelola kelas
sesuai penugasan/wali kelas; admin dapat mengelola semua kelas.

Tab **Rekap** menampilkan rekap bulanan dan opsi cetak.

### 6.5 Absen Manual oleh Admin (Per Guru / Per Batch)

Menu: **Presensi & KBM → Presensi Guru → Absen Manual** (`/admin/presensi/manual`)

1. Pilih tab **Per Guru** atau **Per Batch**.
2. Pilih tanggal (tersedia pintasan **Hari Ini** dan **Kemarin**).
3. Pilih status: **HADIR, IJIN, SAKIT, CUTI, DINAS**.
4. Pilih jenis: **Masuk**, **Pulang**, atau **Keduanya**.
5. Isi jam masuk/pulang dan keterangan.
6. Simpan.

Ketentuan: pada hari aktif (hari ini), hanya boleh mengisi **masuk** atau **pulang** dalam
satu kali simpan; mode **Keduanya** hanya untuk tanggal yang sudah lewat. Tab batch menyediakan
**Isi Hadir**, **Pulang Saja**, dan reset untuk banyak guru sekaligus.

### 6.6 Jadwal BDR (Belajar dari Rumah)

Menu: **Presensi & KBM → Presensi Siswa → Jadwal BDR** (`/admin/bdr`)

1. Klik tambah, pilih kelas dan tanggal BDR, isi keterangan.
2. Simpan, lalu gunakan **Terapkan** agar jadwal dipakai pada presensi kelas.
3. Saat mengisi presensi rombel pada tanggal BDR, gunakan tombol **Semua BDR**.

### 6.7 Kartu QR Siswa

Menu: **Presensi & KBM → Presensi Siswa → Cetak Kartu QR Siswa** (`/admin/presensi/siswa/kartu`)

1. Pilih **desain kartu**: landscape (duplex) atau portrait.
2. Pilih **tata letak cetak**: lembar, bolak-balik, atau ukuran KTP (CR80).
3. Untuk desain portrait, pilih **template** vektor atau latar PNG.
4. Filter kelas/siswa, lalu klik **Cetak Kartu**.
5. Tombol **Buka Scanner QR** membuka scanner untuk uji coba kartu.

Kartu guru dicetak dari **Presensi Guru → Cetak Kartu QR Guru**; guru juga dapat mencetak
kartunya sendiri dari **Kartu QR Saya**.

---

## 7. Jurnal Mengajar Guru

### 7.1 Konsep dan Status Jurnal

Jurnal diisi **per sesi/jam pelajaran** sesuai jadwal. Setiap jurnal memiliki status:

| Status | Arti |
|---|---|
| **Menunggu validasi** | Sudah disimpan guru, belum diperiksa piket/kepala sekolah. |
| **Disetujui** | Sudah divalidasi. Wajib untuk absen pulang pada mode ketat. |
| **Perlu perbaikan / ditolak** | Dikembalikan dengan catatan; guru harus memperbaiki. |

Halaman **Jurnal Mengajar** (`/guru/jurnal`) menampilkan:

- Kartu ringkasan: Bulan Ini, Hari Ini, 7 Hari, Semester.
- **Jadwal Hari Ini** per sesi, dengan penanda `x/y terjurnal` dan jumlah menunggu validasi.
- Tombol per sesi: **Isi**, **Terisi**, **Menunggu validasi**, atau **Perbaiki** (bila ditolak).
- Pencarian dan filter kelas/mapel, serta pemilih bulan.

### 7.2 Membuka Form Jurnal

Ada beberapa cara:

1. Dari **Jadwal Hari Ini** — klik **Isi** pada sesi yang belum terjurnal.
2. Dari **Jadwal Mengajar** (`/guru/jadwal`) — klik sesi pada kartu jadwal.
3. Dari menu **Jurnal Mengajar** — tombol tambah/isi.
4. Saat absen pulang ditolak karena jurnal kurang — ikuti tautan yang muncul.

Form otomatis mengisi tanggal, jam ke, kelas, dan mapel bila dibuka dari jadwal.
Gunakan **Salin jurnal sebelumnya** bila tersedia untuk mempercepat pengisian.

### 7.3 Mengisi Form Jurnal

| Bagian | Keterangan |
|---|---|
| Tanggal | Tanggal pelaksanaan; default hari ini. |
| Jam ke / Kelas / Mapel | Mengikuti jadwal atau mapping guru. |
| **Materi Pokok** | Wajib diisi. |
| Tujuan Pembelajaran | Tujuan sesi. |
| Model Belajar | Mis. diskusi, proyek, praktik. |
| Media / Alat | Media dan alat yang dipakai. |
| Kegiatan Belajar | Ringkasan langkah kegiatan. |
| Profil Pancasila | Pilih satu atau lebih dimensi. |
| Jenis Asesmen | Bentuk penilaian. |
| Refleksi | Refleksi guru/siswa. |
| Tindak Lanjut | Rencana perbaikan/pengayaan. |
| Catatan Khusus | Informasi tambahan bila ada. |

**Menandai siswa tidak hadir:** gunakan pemilih siswa pada form. Tandai siswa yang absen
(hadir default), atau gunakan tombol pilih semua yang belum ditandai. Data ini masuk ke
rekap jurnal dan dapat terhubung dengan poin disiplin siswa.

### 7.4 Menyimpan

- **Simpan Jurnal** — menyimpan dan kembali ke daftar/detail.
- **Simpan & tulis jurnal berikutnya** — menyimpan lalu membuka form sesi berikutnya.

Bila form dibuka dari proses absen pulang, setelah simpan guru diarahkan kembali ke halaman
presensi agar dapat melanjutkan absen pulang.

### 7.5 Memperbaiki Jurnal yang Ditolak

1. Buka **Jurnal Mengajar**, cari sesi berlabel **Perbaiki** atau buka detail jurnal.
2. Baca banner **Perlu perbaikan** dan catatan dari piket.
3. Klik **Perbaiki**, ubah isi sesuai catatan, lalu simpan.
4. Jurnal kembali berstatus **Menunggu validasi**.

### 7.6 Hubungan Jurnal dengan Absen Pulang

- **Wajib jurnal sebelum pulang** (default aktif): semua sesi hari itu harus sudah dijurnal.
  Sesi yang dibuka dari halaman absen akan menampilkan daftar sesi yang menghalangi pulang.
- **Mode ketat** (opsional): jurnal harus sudah **disetujui** piket/kepala sekolah.
- Dinas luar dikecualikan dari kewajiban jurnal.
- Jika tidak ada jadwal mengajar hari itu, kewajiban jurnal tidak berlaku.

---

## 8. Validasi Jurnal oleh Guru Piket / Kepala Sekolah

### 8.1 Siapa yang Memvalidasi

- Guru yang tercatat **bertugas piket hari itu**.
- Kepala sekolah, dan admin (keduanya dapat mengakses menu piket tanpa jadwal piket).
- Guru tidak boleh memvalidasi jurnalnya sendiri (admin dikecualikan).
- Validasi dibatasi sampai **H-7** setelah tanggal jurnal; admin tidak dibatasi.

### 8.2 Langkah Validasi

1. Login sebagai guru piket, buka **Panel Piket** (`/guru/piket`).
2. Pilih **Kehadiran Guru** (`/guru/piket/input_laporan`) — halaman ini menampilkan slot
   jadwal per kelas/sesi, status kehadiran guru, dan status jurnal.
3. Gunakan filter isu: **Tanpa Jurnal**, **Tanpa Scan**, atau **Perlu Nilai**.
4. Klik slot/jurnal untuk membuka detail (isi jurnal, siswa tidak hadir, lampiran).
5. Pilih tindakan:
   - **Setujui** — jurnal dianggap sah.
   - **Tolak** — wajib mengisi catatan perbaikan.
   - **Batalkan validasi** — mengembalikan status ke menunggu.
6. Simpan. Guru akan melihat status dan catatan pada halaman jurnalnya.

### 8.3 Tugas Piket Lain yang Terkait

- **Buku Tamu** (`/guru/piket/tamu`) — catat tamu masuk/keluar.
- **Catatan Kejadian** (`/guru/piket/kejadian`) — catat kejadian dan tindak lanjut.
- **Pemantauan Kelas** (`/guru/piket/absensi`) — pantau kehadiran kelas.
- **Laporan Piket** (`/guru/piket/laporan`) — kelengkapan jurnal, selisih catatan,
  rekap, dan kirim rekap ke grup/channel notifikasi.
- **Input Presensi** — catat kehadiran guru per sesi; data ini dibandingkan dengan jurnal
  (slot berjurnal tetapi guru belum scan akan ditandai).

---

## 9. Monitoring dan Laporan Admin

| Menu | Fungsi |
|---|---|
| **Dashboard** | Ringkasan kehadiran dan aktivitas hari ini. |
| **Monitoring Guru** (`/admin/presensi`) | Presensi guru harian, status masuk/pulang, keterlambatan. |
| **Monitoring Siswa** (`/admin/presensi/siswa/harian`) | Presensi siswa harian per kelas; dapat dicetak PDF. |
| **Kalender & Libur** (`/admin/kalender`) | Atur hari libur; libur memengaruhi perhitungan presensi dan rekap. |
| **Monitoring Jurnal** (`/admin/jurnal`) | Kelengkapan jurnal per sesi, antrean validasi, filter periode/kelas/mapel/guru. |
| **Rekap Jurnal Guru** (`/admin/jurnal/rekap`) | Rekap per guru; ekspor dan cetak. |
| **Rekap Jurnal per Kelas** (`/admin/jurnal/rekap-kelas`) | Rekap per kelas; cetak. |
| **Rekap Bulanan Guru** (`/admin/presensi/rekap`) | Rekap kehadiran guru per bulan. |
| **Rekap Semester Guru** (`/admin/presensi/rekap-semester`) | Rekap semester. |
| **Rekap Denda** (`/admin/presensi/denda`) | Denda keterlambatan/pelanggaran sesuai poin & denda. |
| **Rekap Bulanan Siswa** (`/admin/presensi/siswa/rekap`) | Rekap presensi siswa, ekspor Excel/PDF. |
| **Poin & Reward Guru** (`/admin/reward`) | Poin kehadiran/lembur dan reward. |
| **Poin Disiplin Siswa** (`/admin/poin-siswa`) | Poin pelanggaran/kehadiran siswa. |
| **Surat Teguran Guru** (`/admin/presensi/surat-teguran`) | Terbitkan surat teguran; guru harus mengonfirmasi. |
| **Surat Panggilan Orang Tua** (`/admin/presensi/surat-panggilan`) | Surat panggilan terkait disiplin siswa. |
| **Monitoring Piket** (`/admin/piket/monitoring`) | Kinerja dan laporan piket. |

**Monitoring Jurnal** menampilkan status per slot: terisi, menunggu validasi, disetujui,
tanpa jurnal, tanpa scan, guru pengganti, dan siswa absen. Halaman ini juga menampilkan
antrean validasi serta penjelasan mode ketat bila aktif.

**Persetujuan & Verifikasi**:

- **Izin / Cuti** — setujui/tolak pengajuan; bisa tambah izin manual.
- **Dinas Luar** — setujui tugas luar.
- **Lembur** — setujui/tolak pengajuan lembur.
- **Koreksi Absen** — tinjau permintaan koreksi jam.
- **Verifikasi Hadir** — verifikasi kehadiran yang perlu peninjauan.
- **Pengajuan Wajah** — setujui pendaftaran/perbarui wajah guru.

**QR Event / Acara** (`/admin/presensi/qr`): buat QR baru (nama acara, tanggal, jam, lokasi,
mode statis/berputar, interval), tampilkan/cetak, aktifkan/nonaktifkan, dan lihat rekap.

**Notifikasi**: atur channel per jenis (Telegram/WhatsApp), tautkan bot/nomor, dan pantau
status pengiriman. Rekap piket dapat dikirim ke grup melalui fitur ini.

---

## 10. Tugas Kepala Sekolah

Kepala sekolah memakai akun guru/admin dengan jabatan kepala sekolah. Selain menu guru,
tersedia kelompok **Monitoring KBM**:

- **Monitoring Jurnal** (`/admin/jurnal`)
- **Rekap Jurnal Guru** (`/admin/jurnal/rekap`)
- **Rekap Jurnal per Kelas** (`/admin/jurnal/rekap-kelas`)

Kepala sekolah dapat meninjau dan memvalidasi jurnal seperti guru piket. Fokuskan penggunaan
pada pemeriksaan kelengkapan jurnal, kualitas pengisian, dan tindak lanjut sesi bermasalah.

---

## 11. Alur Harian Ringkas

| Waktu | Pelaku | Kegiatan |
|---|---|---|
| Sebelum jam masuk | Admin | Pastikan tahun/semester aktif, jadwal, piket, dan kiosk siap. |
| Pagi | Siswa | Scan kartu di kiosk (masuk) atau QR di scanner. |
| Pagi | Guru | Absen masuk (Face ID / QR / RFID). |
| Jam pelajaran | Guru | Isi jurnal setiap sesi dari **Jadwal Hari Ini**. |
| Jam pelajaran | Guru piket | Pantau kelas, catat kejadian/tamu, isi kehadiran guru per sesi. |
| Kapan saja | Guru piket | Validasi jurnal (setujui/tolak dengan catatan). |
| Sore | Siswa | Scan kartu (pulang). |
| Sore | Guru | Absen pulang — pastikan jurnal hari itu lengkap/tervalidasi. |
| Akhir hari | Admin | Periksa monitoring guru/siswa dan antrean jurnal. |
| Akhir bulan | Admin | Unduh rekap bulanan guru/siswa, rekap denda, dan rekap jurnal. |

---

## 12. F.A.Q. dan Penanganan Masalah

**"Di luar area lokasi absensi"**
Pindah ke dalam radius lokasi kantor. Jika sedang tugas luar, gunakan **Mode Dinas**.
Jika radius terlalu sempit, ajukan perubahan radius ke admin.

**Kamera tidak muncul / tidak bisa scan**
Pastikan alamat memakai HTTPS, izin kamera diberikan, dan tidak ada aplikasi lain yang
memakai kamera. Coba Chrome versi terbaru.

**NFC tidak berfungsi**
Web NFC hanya didukung Chrome di Android dengan HTTPS. Untuk perangkat lain, gunakan
scanner QR desktop, scanner gun, atau input NIS manual.

**"Kuota WFH habis" / WFH tidak tersedia**
Ajukan izin/cuti, atau absen di kantor. Admin dapat menaikkan kuota bulanan pada
Pengaturan → Batasan & WFH.

**Absen pulang ditolak karena jurnal**
Buka **Jurnal Mengajar**, lengkapi jurnal semua sesi hari itu, lalu ulangi absen pulang.
Jika mode ketat aktif, tunggu persetujuan guru piket.

**Jurnal tidak bisa disetujui piket**
Penyebab umum: guru memvalidasi jurnal sendiri, di luar H-7, atau tidak bertugas piket hari itu.
Pastikan jadwal piket sudah dibuat admin.

**"Belum ada guru piket terjadwal hari ini"**
Admin harus membuat jadwal piket (**Jadwal Petugas Piket → Generate Otomatis**).

**Jurnal tidak menampilkan jadwal hari ini**
Periksa jadwal pelajaran dan tanggal. Pastikan jadwal sudah diisi untuk hari tersebut,
bukan hari libur, dan guru punya mapping kelas–mapel.

**Form jurnal tidak menampilkan kelas/mapel yang diajar**
Perbaiki **Edit Jabatan → Mapping Kelas × Mapel** pada Data Guru.

**Siswa tidak muncul di presensi rombel**
Pastikan siswa berstatus aktif dan terdaftar di kelas tersebut pada tahun pelajaran aktif.

**Scan kartu siswa gagal di kiosk**
Periksa UID kartu sudah didaftarkan, kiosk dalam mode yang benar, GPS terkunci,
dan siswa aktif.

**Halaman scanner/kiosk terkunci (403)**
Admin harus mengisi **Token Kiosk** di Pengaturan → Maintenance, lalu buka
`/scanner?k=TOKEN` atau `/kiosk?k=TOKEN` sekali pada perangkat itu.

**Akun terkunci setelah salah password 3 kali**
Tunggu 5 menit, atau minta admin mereset percobaan login di **Manajemen Pengguna**.

**Password lupa**
Admin mereset password: guru menjadi `123456`, siswa menjadi NISN.

**Import Excel gagal**
Periksa jumlah baris (guru maks. 1.000; siswa maks. 2.000), NIP/NIS harus angka,
username 4–30 karakter, password minimal 5 karakter, dan tidak ada username ganda.

**Wajah tidak dikenali**
Daftarkan ulang wajah dari Profil, pastikan pencahayaan cukup, dan minta admin menyetujui
pengajuan wajah. Bila tetap gagal, admin dapat mengubah mode kamera atau threshold.

**Data presensi kelas tertimpa**
Simpan presensi hanya sekali per kelas/tanggal. Bila perlu koreksi, gunakan koreksi/ubah
jam alih-alih menyimpan ulang seluruh kelas.

---

## 13. Lampiran: Checklist Setup dan Daftar Status

### 13.1 Checklist Go-Live

- [ ] Tahun pelajaran dan semester aktif sudah diatur.
- [ ] Jurusan (bila ada), kelas/rombel, dan wali kelas sudah lengkap.
- [ ] Mata pelajaran dan jadwal pelajaran sudah terisi.
- [ ] Data guru sudah diimport/diinput, kartu RFID terdaftar, mapping kelas–mapel benar.
- [ ] Data siswa sudah diimport/diinput dan kartu RFID terdaftar.
- [ ] Wajah guru sudah didaftarkan dan disetujui.
- [ ] Jadwal piket sudah digenerate.
- [ ] Shift kerja (bila dipakai) sudah dibuat dan ditugaskan.
- [ ] Lokasi kantor dan radius sudah diatur.
- [ ] Jam kerja, grace period, dan kebijakan jurnal sudah dipilih.
- [ ] Token kiosk sudah diisi dan perangkat kiosk sudah terdaftar.
- [ ] Notifikasi Telegram/WhatsApp sudah ditautkan dan diuji.
- [ ] Kartu QR guru dan siswa sudah dicetak.
- [ ] Admin, guru, dan siswa sudah mencoba login serta satu siklus presensi penuh.

### 13.2 Daftar Status Presensi Siswa

| Status | Arti |
|---|---|
| HADIR | Siswa hadir di sekolah. |
| SAKIT | Tidak hadir karena sakit (dengan keterangan). |
| IZIN | Tidak hadir dengan izin. |
| ALPA | Tidak hadir tanpa keterangan yang sah. |
| BDR | Belajar dari rumah sesuai jadwal BDR. |
| TANPA KETERANGAN (-/-) | Belum ditentukan. |

### 13.3 Daftar Status Validasi Jurnal

| Status | Arti |
|---|---|
| MENUNGGU / Menunggu validasi | Sudah diisi, belum divalidasi. |
| DISETUJUI | Sudah divalidasi piket/kepala sekolah. |
| DITOLAK / Perlu perbaikan | Dikembalikan dengan catatan untuk diperbaiki. |

### 13.4 Ringkasan Menu Cepat

| Kebutuhan | Menu |
|---|---|
| Absen guru harian | `/presensi` |
| Riwayat presensi guru | Presensi Saya (`/guru/absensi`) |
| Isi jurnal | Jurnal Mengajar (`/guru/jurnal`) |
| Validasi jurnal | Panel Piket → Kehadiran Guru (`/guru/piket/input_laporan`) |
| Presensi kelas | Input Presensi Rombel (`/presensisiswa`) |
| Kiosk RFID | `/kiosk?k=TOKEN` |
| Scanner QR | `/scanner?k=TOKEN` |
| Monitoring jurnal | `/admin/jurnal` |
| Pengaturan presensi | `/settings` (tab Presensi) |
| Lokasi/radius | `/admin/presensi/lokasi` |
| Token kiosk | `/settings` → Maintenance |

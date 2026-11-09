# Porting method kustom model CI4 → Eloquent Laravel

Aplikasi asal: `~/workspace/absen-ci4` (CodeIgniter 4.6.4). Target: `~/workspace/absen-laravel` (Laravel 13.40.0).
Dokumen ini adalah inventarisasi lengkap method kustom di model CI4 (di luar `__construct`),
status pemanggilannya di aplikasi Laravel, tabel yang disentuh, dan cakupan `BaseController`.
Dibuat otomatis dari `app/Models` CI4, `database/migrations` Laravel, dan grep call-site
di `app/` + `routes/` Laravel pada 2026-11-09.

**Catatan penghapusan 2026-11-09:** empat model "hantu" — `NilaiModel`,
`RaporModel`, `PanitiaModel`, `TujuanPembelajaranModel` — dihapus beserta
`UserpanitiaController` atas konfirmasi pengguna bahwa tabelnya
(`nilai_proses`, `rapor_nilai_harian`, `master_panitia`,
`e_tujuan_pembelajaran`) memang tidak dipakai. Bagian per-model di bawah ini
tetap dipertahankan sebagai arsip inventarisasi.

## Pengecualian cakupan (keputusan pengguna 2026-11-09)

**`CbtModel` (93 method), `RaporModel` (70 method), `CbtSiswaModel` (10 method), dan
`CbtReportModel` (6 method) TIDAK di-port.** File Laravel keempat model tersebut
dibiarkan apa adanya (hanya `$table`/`$fillable`/`$casts`). Daftar method keempat
model tetap dicatat di bagian akhir dokumen ini sebagai arsip, dengan tanda `EXCLUDED`.
Total method yang di-port berkurang 179 — sisa **431 method di 39 model**.
(Angka brief awal menyebut 94/71/11/7 — hitungan terukur dari file adalah 93/70/10/6.)

## Ringkasan

- Total method kustom CI4: **610** (43 model)
- Dikecualikan (CbtModel + RaporModel + CbtSiswaModel + CbtReportModel): **179**
- Dalam cakupan porting: **431** (39 model)
- Dipanggil di aplikasi Laravel: **264** (dalam cakupan: **255**; tidak dipanggil: 346)
- Dalam cakupan dan menyentuh tabel yang belum ada migrasinya: **310** — kodenya tetap di-port dengan komentar `// TODO: tabel X belum ada migrasinya`
- Sudah dicakup `BaseController` (helper `auth*`/`getSettingRow`/`saveAuditLog`/`updateMasterData`): **14**
- Tabel di migrasi Laravel: **57** (52 tabel aplikasi + 5 infra: cache, jobs, sessions, + 2 standar)
- 3 model tanpa method kustom: `FaceRegistrationRequestModel`, `RewardModel`, `ShiftAnggotaModel`

## Cara membaca tabel per model

- **Dipanggil**: jumlah file di `app/` + `routes/` Laravel yang memanggil `->namaMethod(`; daftar file dipersingkat ke path relatif dari root project.
- **Tabel**: tabel yang terdeteksi di badan method (heuristik regex `table()`/`from()`/`join()`/SQL mentah).
- **Tabel blm ada**: tabel yang TIDAK ada di migrasi Laravel (termasuk ±26 tabel inti tanpa definisi skema di migrasi CI4 — lihat `PORTING.md`).
- **BaseController**: method sudah diimplementasikan sebagai helper di `BaseController` (mis. `authUser`, `getSettingRow`).
- **Catatan**: `RAW-SQL` = memakai `$this->db->query()`; `X→ModelY` = memanggil model lain; `COLLISION` = nama bentrok dengan method Eloquent/CI4 base (`find`, `save`, dsb.) — perlu perhatian khusus saat porting.
- Bentuk return dipertahankan dari CI4: `getResultArray()`/`getRowArray()` → array; `getResult()`/`getRow()` → object. Periksa call site sebelum porting (lihat kolom **Dipanggil**).

## Method bentrok nama Eloquent/CI4 base

Method berikut namanya sama dengan method bawaan Eloquent/CI4 Model — jangan override tanpa memeriksa pemakaian:

- `AttendanceSettingModel.first`
- `MasterModel.create`
- `PanitiaModel.create`
- `RaporModel.exists`
- `SettingsModel.truncate`

---

## MasterModel — 69 method (dipanggil 32, terhalang tabel 57)

Tabel model: `-`. File CI4: `app/Models/MasterModel.php`. File Laravel: `app/Models/MasterModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getAllSiswa(int $tp, int $smt, int $limit = 10, int $offset = 0, ?string $search = null): array` | 1 (C:DatakelasController.php) | buku_induk, kelas_siswa, master_kelas, master_siswa | buku_induk, kelas_siswa, master_kelas, master_siswa | - | ret:getResult |
| `getTotalSiswa(int $tp, int $smt, ?string $search = null): int` | 1 (C:DashboardController.php) | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:countAllResults |
| `getSiswa(int $id): ?object` | 1 (C:UsersiswaController.php) | master_siswa | master_siswa | - | ret:getRow |
| `getSiswaByUsername(string $username): ?object` | 0 | master_siswa | master_siswa | - | ret:getRow |
| `saveSiswa(array $data): bool` | 0 | master_siswa | master_siswa | - | - |
| `updateSiswa(int $id, array $data): bool` | 0 | master_siswa | master_siswa | - | - |
| `deleteSiswa(int $id): bool` | 0 | master_siswa | master_siswa | - | - |
| `getSiswaById(int $id): ?object` | 2 (C:Admin/PoinSiswaController.php, C:DatasiswaController.php) | buku_induk, master_siswa | buku_induk, master_siswa | - | ret:getRow |
| `getAllGuru()` | 4 (C:Admin/JadwalController.php, C:DatakelasController.php, C:Guru/PiketController.php, C:UserpanitiaController.php) | master_guru | master_guru | - | ret:getResult |
| `siswaListBuilder(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false)` | 0 | buku_induk, kelas_siswa, master_kelas, master_siswa, users | buku_induk, kelas_siswa, master_kelas, master_siswa, users | - | - |
| `getSiswaPage(int $tp, int $smt, int $offset, int $limit, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): array` | 1 (C:DatasiswaController.php) | - | - | - | ret:getResult |
| `getSiswaTotalPage(int $tp, int $smt, string $status = '0', ?int $kelas = null, ?string $search = null, bool $tanpaKelas = false): int` | 1 (C:DatasiswaController.php) | - | - | - | ret:countAllResults |
| `getSiswaStats(int $tp, int $smt): array` | 1 (C:DatasiswaController.php) | buku_induk, kelas_siswa, master_siswa, users | buku_induk, kelas_siswa, master_siswa, users | - | RAW-SQL; ret:getRow |
| `getSiswaByArrId(array $ids): array` | 0 | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | - | ret:getResult |
| `getSiswaByArrNisn(array $arr_nisn, array $arr_nis, array $arr_username): array` | 0 | master_siswa | master_siswa | - | ret:getResult |
| `getSiswaByUsernames(array $usernames): array` | 1 (C:DatasiswaController.php) | master_siswa | master_siswa | - | ret:getResult |
| `getSiswaKelasBaru(int $tp, int $smt): array` | 0 | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | - | ret:getResult |
| `getDataSiswaByKelas(int $tp, int $smt, int $id_kelas, int $offset = 0, int $limit = 0, ?string $search = null): array` | 0 | kelas_siswa, master_kelas, master_siswa, users | kelas_siswa, master_kelas, master_siswa, users | - | ret:getResult |
| `getDataSiswaByKelasPage(int $tp, int $smt, int $id_kelas, ?string $search = null): int` | 0 | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:countAllResults |
| `getSiswaByKelas(int $id_kelas, int $tp, int $smt): array` | 2 (C:Guru/JurnalController.php, C:PresensiSiswaController.php) | - | - | - | - |
| `getAllDataGuru(int $tp, int $smt): array` | 1 (C:DataguruController.php) | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp, users | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp, users | - | ret:getResult |
| `getDataGuru(int $tp, int $smt, ?string $search = null): array` | 2 (C:DataguruController.php, C:UserguruController.php) | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp | - | ret:getResult |
| `getGuruById(int $id, ?int $id_tp = null, ?int $id_smt = null): ?object` | 3 (C:DataguruController.php, C:GuruController.php, C:UserpanitiaController.php) | jabatan_guru, level_guru, master_guru, master_kelas | jabatan_guru, level_guru, master_guru, master_kelas | - | ret:getRow |
| `getDetailJabatanGuru(int $id_guru): array` | 1 (C:DataguruController.php) | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp | jabatan_guru, level_guru, master_guru, master_kelas, master_smt, master_tp | - | ret:getResult |
| `getJabatanGuru(int $id_guru, int $tp, int $smt): ?object` | 1 (C:DataguruController.php) | jabatan_guru, level_guru, master_guru, master_kelas | jabatan_guru, level_guru, master_guru, master_kelas | - | ret:getRow |
| `getGuruMapel(int $tp, int $smt, ?array $ids_guru = null): array` | 0 | jabatan_guru, master_guru | jabatan_guru, master_guru | - | ret:getResult |
| `updateGuru(int $id, array $data): bool` | 0 | master_guru | master_guru | - | - |
| `deleteGuru($id): bool` | 0 | master_guru | master_guru | - | - |
| `deleteGuruLinked(int $id_guru): bool` | 1 (C:DataguruController.php) | master_guru, master_kelas, users, users_groups | master_guru, master_kelas, users, users_groups | - | ret:getRow,countAllResults |
| `getAllKelas(int $tp, int $smt): array` | 9 (C:Admin/JadwalController.php, C:Admin/PoinSiswaController.php, C:Admin/PresensiBdrController.php, C:DashboardController.php, C:DataguruController.php, C:DatasiswaController.php, C:Guru/PiketController.php, C:Guru/PoinSiswaController.php, C:PresensiSiswaController.php) | master_guru, master_kelas | master_guru, master_kelas | - | ret:getResult |
| `getKelasById(int $id): ?object` | 1 (C:DatasiswaController.php) | master_kelas | master_kelas | - | ret:getRow |
| `getKelasByLevel(int $tp, int $smt, int $level): array` | 0 | master_kelas | master_kelas | - | ret:getResult |
| `saveKelas(array $data): bool` | 0 | master_kelas | master_kelas | - | - |
| `updateKelas(int $id, array $data): bool` | 0 | master_kelas | master_kelas | - | - |
| `deleteKelas(int $id): bool` | 0 | master_kelas | master_kelas | - | - |
| `countSiswaInKelas(int $idKelas, int $tp, int $smt): int` | 1 (C:DatakelasController.php) | kelas_siswa | kelas_siswa | - | ret:countAllResults |
| `getAllMapel(?int $jenjang = null): array` | 3 (C:Admin/JadwalController.php, C:DataguruController.php, C:DatajurusanController.php) | master_mapel | master_mapel | - | ret:getResult |
| `getDataKelompokMapel()` | 1 (C:DatamapelController.php) | master_kelompok_mapel | master_kelompok_mapel | - | ret:getResult |
| `getDataSubKelompokMapel()` | 1 (C:DatamapelController.php) | master_kelompok_mapel | master_kelompok_mapel | - | ret:getResult |
| `getKategoriKelompokMapel()` | 0 | master_kelompok_mapel | master_kelompok_mapel | - | ret:getResult |
| `getMapel(int $id): ?object` | 1 (C:UserguruController.php) | master_mapel | master_mapel | - | ret:getRow |
| `saveMapel(array $data): bool` | 0 | master_mapel | master_mapel | - | - |
| `updateMapel(int $id, array $data): bool` | 0 | master_mapel | master_mapel | - | - |
| `deleteMapel(int $id): bool` | 0 | master_mapel | master_mapel | - | - |
| `getAllJurusan(): array` | 1 (C:DatajurusanController.php) | jurusan, master_jurusan | jurusan, master_jurusan | - | ret:getResult |
| `getJurusan(int $id): ?object` | 0 | master_jurusan | master_jurusan | - | ret:getRow |
| `getDropdownKelas(int $tp, int $smt): array` | 0 | - | - | - | - |
| `getDropdownMapel(): array` | 0 | - | - | - | - |
| `getDropdownGuru(): array` | 0 | - | - | - | - |
| `generateKelasSiswaId(): int` | 3 (C:DatakelasController.php, C:DatasiswaController.php, S:DapodikSyncService.php) | kelas_siswa | kelas_siswa | - | ret:getRowArray,getRow |
| `create(string $table, array $data)` | 5 (C:DataguruController.php, C:DatakelasController.php, C:DatamapelController.php, C:DatasiswaController.php, C:UserpanitiaController.php) | - | - | - | COLLISION |
| `insertBatchData(string $table, array $data)` | 1 (C:DatasiswaController.php) | - | - | - | - |
| `updateData(string $table, array $data, string $pk, $id)` | 0 | - | - | BaseController::updateMasterData | - |
| `updateBatchData(string $table, array $data, string $pk)` | 1 (C:DatasiswaController.php) | - | - | - | - |
| `hapus(string $table, $ids, string $pk): bool` | 3 (C:DatakelasController.php, C:DatasiswaController.php, C:DatatahunController.php) | - | - | - | - |
| `getJmlHariEfektif(string $id)` | 1 (C:DatatahunController.php) | master_hari_efektif | master_hari_efektif | - | ret:getRow |
| `getGuruMapelKelas(int $idGuru, int $tp, int $smt)` | 1 (C:Guru/JadwalController.php) | jabatan_guru | jabatan_guru | - | ret:getRow |
| `getAllKelasByArrayId(int $tp, int $smt, array $ids): array` | 1 (C:Guru/JadwalController.php) | master_kelas | master_kelas | - | ret:getResult |
| `getLevelsByJenjang(int $jenjang): array` | 0 | - | - | - | - |
| `getAgamaSiswa(): array` | 0 | master_siswa | master_siswa | - | ret:getResult |
| `getAllKodeKelas($tp, $smt)` | 0 | master_kelas | master_kelas | - | ret:getResult |
| `getAllJabatanGuru(int $id_guru): array` | 0 | jabatan_guru | jabatan_guru | - | ret:getResult |
| `getKodeKelompokMapel(): array` | 0 | master_kelompok_mapel | master_kelompok_mapel | - | ret:getResult |
| `getAllStatusMapel(?array $arrKelompok = null, ?string $arrMapel = null): array` | 0 | master_mapel | master_mapel | - | ret:getResult |
| `getDistinctTahunLulus(): array` | 0 | buku_induk | buku_induk | - | ret:getResult |
| `getDistinctKelasAkhir(): array` | 0 | buku_induk | buku_induk | - | ret:getResult |
| `getAlumniByTahun(string $tahun, ?string $kelas = null): array` | 0 | buku_induk, master_siswa | buku_induk, master_siswa | - | ret:getResult |
| `getAlumniFilter(string $filter): array` | 0 | buku_induk, master_siswa | buku_induk, master_siswa | - | ret:getResult |
| `getAlumniById(int $id)` | 0 | buku_induk, master_siswa | buku_induk, master_siswa | - | ret:getRow |

## PiketModel — 49 method (dipanggil 40, terhalang tabel 36)

Tabel model: `piket_kejadian`. File CI4: `app/Models/PiketModel.php`. File Laravel: `app/Models/PiketModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `filterSiswaAktif($builder, string $exprSiswa): void` | 1 (C:Admin/KartuSiswaController.php) | buku_induk | buku_induk | - | - |
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | CURRENT_TIMESTAMP | CURRENT_TIMESTAMP | - | - |
| `safeAddColumn($table, $column, $definition)` | 0 | - | - | - | - |
| `getPresensiGuru($tanggal)` | 1 (C:Guru/PiketController.php) | piket_presensi_guru | piket_presensi_guru | - | ret:getResult |
| `getPresensiGuruMap(string $tanggal): array` | 1 (C:Guru/PiketController.php) | piket_presensi_guru | piket_presensi_guru | - | ret:getResult |
| `isDuplicateKeyError(\Throwable $e): bool` | 2 (C:Guru/AttendanceController.php, S:GuruTerawalService.php) | - | - | - | - |
| `savePresensiGuru($data)` | 1 (C:Guru/PiketController.php) | milik, piket_presensi_guru, tak | milik, piket_presensi_guru, tak | - | ret:getRow |
| `formalKePiket(?string $formal, bool $adaJamMasuk): ?string` | 0 | - | - | - | - |
| `piketKeFormal(string $st): ?string` | 0 | - | - | - | - |
| `konflikKehadiran(string $piket, ?string $formal, bool $adaJamMasuk): ?string` | 0 | - | - | - | - |
| `syncPiketKeFormal(array $batch, string $tanggal): array` | 3 (C:Admin/PiketController.php, C:CronControllerController.php, C:Guru/PiketController.php) | buku_induk, presensi_siswa | buku_induk | - | ret:getRow,insertID |
| `syncTelatOtomatis(string $tanggal, ?int $idKelas = null, array $onlyIds = []): array` | 2 (C:Guru/PiketController.php, C:Guru/PresensiSiswaQrController.php) | buku_induk, piket_absensi, presensi_siswa | buku_induk, piket_absensi | - | ret:getResult,countAllResults |
| `syncAlphaOtomatis(string $tanggal, ?int $idKelas = null, array $onlyIds = [], int $tpId = 0, int $smtId = 0): array` | 2 (C:CronControllerController.php, C:Guru/PiketController.php) | buku_induk, kelas_siswa, piket_absensi, presensi_siswa | buku_induk, kelas_siswa, piket_absensi | - | ret:getResult,countAllResults |
| `terapkanBdr(string $tanggal): array` | 1 (C:Admin/PresensiBdrController.php) | piket_absensi, presensi_siswa | piket_absensi | - | ret:getResult |
| `getRealSiswaRekapSekolah(string $tanggal, int $tpId, int $smtId): array` | 0 | kelas_siswa, master_kelas, presensi_siswa | kelas_siswa, master_kelas | - | ret:getResult |
| `getRekapSiswaLaporan(string $tanggal, int $tpId, int $smtId, int $filterKelas = 0): array` | 2 (C:CronControllerController.php, C:Guru/PiketController.php) | kelas_siswa, master_kelas, piket_absensi, presensi_siswa | kelas_siswa, master_kelas, piket_absensi | - | ret:getResult |
| `jamPulangSetting(): string` | 0 | absensi_setting | - | - | ret:getRow |
| `sudahLewatJamPulang(string $tanggal): bool` | 2 (C:CronControllerController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getSiswaTanpaPulang(string $tanggal, ?int $idKelas = null, int $tpId = 0, int $smtId = 0): array` | 2 (C:CronControllerController.php, C:Guru/PiketController.php) | buku_induk, kelas_siswa, master_kelas, master_siswa, presensi_siswa | buku_induk, kelas_siswa, master_kelas, master_siswa | - | ret:getResult |
| `getSiswaSeringAlpha(int $tpId = 0, int $smtId = 0, int $batas = 3, int $limit = 30): array` | 1 (C:Guru/PiketController.php) | absensi_setting, kelas_siswa, master_kelas, master_siswa, setting | kelas_siswa, master_kelas, master_siswa, setting | - | ret:getResult,getRow |
| `labelRentangMinggu(string $senin, string $ahad, array $namaBulan): string` | 0 | - | - | - | - |
| `getDivergensi(string $tanggal, ?int $idKelas = null, int $limit = 50): array` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | master_kelas, master_siswa, piket_absensi, presensi_siswa | master_kelas, master_siswa, piket_absensi | - | ret:getResult |
| `parseSiswaAbsent(?string $raw): array` | 1 (C:Admin/JurnalController.php) | - | - | - | - |
| `infoAbsenJurnal(object $j): array` | 2 (C:Guru/JurnalController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getTamuById($id)` | 1 (C:Guru/PiketController.php) | piket_tamu | piket_tamu | - | ret:getRow |
| `getTamu($tanggal = null)` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | piket_tamu | piket_tamu | - | ret:getResult |
| `saveTamu($data)` | 1 (C:Guru/PiketController.php) | piket_tamu | piket_tamu | - | - |
| `deleteTamu($id)` | 1 (C:Guru/PiketController.php) | piket_tamu | piket_tamu | - | - |
| `updateTamuKeluar($id)` | 1 (C:Guru/PiketController.php) | piket_tamu | piket_tamu | - | - |
| `getKejadian($tanggal = null, $tp = null, $smt = null)` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | kelas_siswa, master_kelas, master_siswa, piket_kejadian | kelas_siswa, master_kelas, master_siswa, piket_kejadian | - | ret:getResult |
| `saveKejadian($data)` | 1 (C:Guru/PiketController.php) | piket_kejadian | piket_kejadian | - | - |
| `deleteKejadian($id)` | 1 (C:Guru/PiketController.php) | piket_kejadian | piket_kejadian | - | - |
| `updateKejadianStatus($id, $status)` | 1 (C:Guru/PiketController.php) | piket_kejadian | piket_kejadian | - | - |
| `getKejadianById($id)` | 1 (C:Guru/PiketController.php) | master_siswa, piket_kejadian | master_siswa, piket_kejadian | - | ret:getRow |
| `getAbsensiByKelas($tanggal, $id_kelas)` | 1 (C:Guru/PiketController.php) | piket_absensi | piket_absensi | - | ret:getResult |
| `getRealSiswaMap(string $tanggal, array $ids): array` | 1 (C:Guru/PiketController.php) | presensi_siswa | - | - | ret:getResult |
| `saveAbsensiBatch($dataBatch)` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | buku_induk, piket_absensi | buku_induk, piket_absensi | - | ret:getResult,getRow |
| `normalisasiJam(?string $jam): ?string` | 0 | - | - | - | - |
| `saveJamManual(array $rows, string $tanggal, int $idKelas, ?int $pencatatGuruId = null, ?string $ip = null): array` | 1 (C:Guru/PiketController.php) | buku_induk, presensi_siswa | buku_induk | - | ret:getRow |
| `getJurnalHarian($tanggal, array $filters = [])` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | guru_jurnal, master_guru, master_kelas, master_mapel | guru_jurnal, master_guru, master_kelas, master_mapel | - | ret:getResult |
| `getAbsensiGuruHarian(string $tanggal): array` | 1 (C:Guru/PiketController.php) | absensi_harian, master_guru | master_guru | - | ret:getResult |
| `getAllGuruWithAbsensi(string $tanggal): array` | 3 (C:Admin/PiketController.php, C:CronControllerController.php, C:Guru/PiketController.php) | absensi_harian, master_guru | master_guru | - | ret:getResult |
| `getJurnalById(int $id)` | 1 (C:Guru/PiketController.php) | guru_jurnal, kelas_jadwal_mapel, master_guru, master_kelas, master_mapel | guru_jurnal, kelas_jadwal_mapel, master_guru, master_kelas, master_mapel | - | ret:getRow |
| `countSiswaKelas(int $idKelas, ?int $idTp = null, ?int $idSmt = null): int` | 1 (C:Guru/PiketController.php) | kelas_siswa | kelas_siswa | - | ret:countAllResults |
| `countSiswaAktifKelas(int $idKelas, ?int $idTp = null, ?int $idSmt = null): int` | 1 (C:Guru/PiketController.php) | kelas_siswa | kelas_siswa | - | ret:getRow |
| `getAlasanGuruMap(string $tanggal): array` | 2 (C:CronControllerController.php, C:Guru/PiketController.php) | absensi_harian, absensi_izin, master_guru | master_guru | - | ret:getResult |
| `getSiswaNamesByIds(array $ids): array` | 1 (C:Admin/JurnalController.php) | master_siswa | master_siswa | - | ret:getResult |
| `getJurnalFilterOptions(string $tanggal): array` | 0 | guru_jurnal, master_guru, master_kelas, master_mapel | guru_jurnal, master_guru, master_kelas, master_mapel | - | ret:getResult |
| `getStats($tanggal)` | 2 (C:Admin/PiketController.php, C:Guru/PiketController.php) | - | - | - | ret:countAllResults |

## KelasModel — 35 method (dipanggil 11, terhalang tabel 32)

Tabel model: `-`. File CI4: `app/Models/KelasModel.php`. File Laravel: `app/Models/KelasModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getCatatanMapelKelas($kelas, $mapel, $id_tp, $id_smt)` | 0 | kelas_catatan_mapel | kelas_catatan_mapel | - | ret:getResult |
| `getCatatanMapelSiswa($id_tp, $id_smt, $id_kelas, $id_mapel)` | 0 | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:getResult |
| `getAllCatatanMapelSiswa($id_siswa, $id_mapel, $id_tp, $id_smt)` | 0 | kelas_catatan_mapel | kelas_catatan_mapel | - | ret:getResult |
| `getGuruMapelKelas($id_guru, $tp, $smt)` | 1 (C:Guru/JadwalController.php) | jabatan_guru, master_guru, master_kelas | jabatan_guru, master_guru, master_kelas | - | ret:getRow |
| `getJadwalKbm($tp, $smt, $kelas)` | 2 (C:Admin/JadwalController.php, C:Guru/JadwalController.php) | kelas_jadwal_kbm | kelas_jadwal_kbm | - | ret:getRow |
| `getJadwalMapelByMapel($kelas, $mapel, $tp, $smt)` | 0 | kelas_jadwal_mapel, master_mapel | kelas_jadwal_mapel, master_mapel | - | ret:getResult |
| `getCatatanKelas($kelas, $id_tp, $id_smt)` | 0 | kelas_catatan_wali | kelas_catatan_wali | - | ret:getResult |
| `getCatatanSiswa($id_tp, $id_smt, $id_kelas)` | 0 | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:getResult |
| `getAllCatatanSiswa($id_siswa, $id_tp, $id_smt)` | 0 | kelas_catatan_wali | kelas_catatan_wali | - | ret:getResult |
| `getStrukturKelas($kelas)` | 1 (C:DatakelasController.php) | kelas_struktur | kelas_struktur | - | ret:getRow |
| `dummyStruktur()` | 0 | - | - | - | - |
| `loadJadwalSiswaHariIni($id_tp, $id_smt, $id_kelas, $id_hari)` | 0 | kelas_jadwal_mapel, master_mapel | kelas_jadwal_mapel, master_mapel | - | ret:getResult |
| `loadJadwalSiswaSeminggu($id_tp, $id_smt, $id_kelas)` | 0 | kelas_jadwal_mapel, master_mapel | kelas_jadwal_mapel, master_mapel | - | ret:getResult |
| `getStatusMateriSiswaByJadwal($id_siswa, $arr_id_kjm)` | 0 | log_materi | log_materi | - | ret:getResult |
| `getCatatanMapelBySiswa($id_kelas, $id_tp, $id_smt)` | 0 | kelas_catatan_mapel, master_guru | kelas_catatan_mapel, master_guru | - | ret:getResult |
| `getCatatanSiswaBySiswa($id_kelas, $id_tp, $id_smt)` | 0 | jabatan_guru, kelas_catatan_wali, master_guru | jabatan_guru, kelas_catatan_wali, master_guru | - | ret:getResult |
| `getCatatanMapelSiswaDetail($id_catatan)` | 0 | jabatan_guru, kelas_catatan_mapel, level_guru, master_guru, master_mapel | jabatan_guru, kelas_catatan_mapel, level_guru, master_guru, master_mapel | - | ret:getRow |
| `getCatatanKelasSiswaDetail($id_catatan)` | 0 | jabatan_guru, kelas_catatan_wali, level_guru, master_guru, master_kelas | jabatan_guru, kelas_catatan_wali, level_guru, master_guru, master_kelas | - | ret:getRow |
| `getReading($table, $id_catatan)` | 0 | - | - | - | ret:getRow |
| `getKelasList($tp, $smt)` | 1 (C:DatakelasController.php) | jabatan_guru, master_guru, master_jurusan, master_kelas | jabatan_guru, master_guru, master_jurusan, master_kelas | - | ret:getResult |
| `getAllKelas()` | 9 (C:Admin/JadwalController.php, C:Admin/PoinSiswaController.php, C:Admin/PresensiBdrController.php, C:DashboardController.php, C:DataguruController.php, C:DatasiswaController.php, C:Guru/PiketController.php, C:Guru/PoinSiswaController.php, C:PresensiSiswaController.php) | master_guru, master_jurusan, master_kelas | master_guru, master_jurusan, master_kelas | - | ret:getResult |
| `get_jurusan()` | 0 | master_jurusan | master_jurusan | - | ret:getResult |
| `getJurusanById($id)` | 0 | master_jurusan | master_jurusan | - | ret:getRow |
| `getLevel($jenjang)` | 0 | level_guru | level_guru | - | ret:getResult |
| `get_guru()` | 0 | master_guru | master_guru | - | ret:getResult |
| `get_one($id, $id_tp = null, $id_smt = null)` | 1 (C:DatakelasController.php) | jabatan_guru, level_kelas, master_guru, master_jurusan, master_kelas | jabatan_guru, level_kelas, master_guru, master_jurusan, master_kelas | - | ret:getRow |
| `get_siswa_kelas($id, $tp, $smt)` | 1 (C:DatakelasController.php) | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:getResult |
| `getKelasSiswa($id, $tp, $smt)` | 0 | - | - | - | - |
| `getNamaKelasById($arr_id)` | 0 | master_kelas | master_kelas | - | ret:getResult |
| `getNamaKelasByKode($arr_kode)` | 0 | master_kelas | master_kelas | - | ret:getResult |
| `getNamaKelasByNama($id_tp, $id_smt)` | 2 (C:DatakelasController.php, C:DatasiswaController.php) | master_kelas | master_kelas | - | ret:getResult |
| `get_siswa_kelas_baru($id_tp, $id_smt)` | 1 (C:DatakelasController.php) | kelas_siswa, master_kelas | kelas_siswa, master_kelas | - | ret:getResult |
| `getKelasEkskul($kelas, $tp, $smt)` | 0 | kelas_ekstra | kelas_ekstra | - | ret:getResult |
| `getWaliKelas($tp, $smt)` | 1 (C:DatakelasController.php) | jabatan_guru, master_guru | jabatan_guru, master_guru | - | ret:getResult |
| `get_status_siswa_kelas($id_kelas, $id_tp, $id_smt)` | 1 (C:DatakelasController.php) | kelas_siswa | kelas_siswa | - | ret:getResult |

## ElearningModel — 29 method (dipanggil 22, terhalang tabel 26)

Tabel model: `-`. File CI4: `app/Models/ElearningModel.php`. File Laravel: `app/Models/ElearningModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getBulan()` | 0 | bulan | bulan | - | ret:getResult |
| `getAllKodeMapel()` | 0 | master_mapel | master_mapel | - | ret:getResult |
| `getMateriSiswa($id_kelas, $tgl, $jenis)` | 0 | kelas_jadwal_materi, kelas_materi, master_mapel | kelas_jadwal_materi, kelas_materi, master_mapel | - | ret:getResult |
| `getNilaiMateriSiswa($id_siswa)` | 0 | kelas_jadwal_materi, kelas_materi, log_materi, master_mapel | kelas_jadwal_materi, kelas_materi, log_materi, master_mapel | - | ret:getResult |
| `getAllMapel()` | 3 (C:Admin/JadwalController.php, C:DataguruController.php, C:DatajurusanController.php) | master_mapel | master_mapel | - | ret:getResult |
| `getMapelArray()` | 4 (C:Admin/JadwalController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getAllGuru()` | 4 (C:Admin/JadwalController.php, C:DatakelasController.php, C:Guru/PiketController.php, C:UserpanitiaController.php) | master_guru | master_guru | - | ret:getResult |
| `getGuruMapelKelas($id_guru, $tp, $smt)` | 1 (C:Guru/JadwalController.php) | jabatan_guru, level_guru, master_guru, master_kelas | jabatan_guru, level_guru, master_guru, master_kelas | - | ret:getRow |
| `getAllKelas($tp = null, $smt = null, $level = null)` | 9 (C:Admin/JadwalController.php, C:Admin/PoinSiswaController.php, C:Admin/PresensiBdrController.php, C:DashboardController.php, C:DataguruController.php, C:DatasiswaController.php, C:Guru/PiketController.php, C:Guru/PoinSiswaController.php, C:PresensiSiswaController.php) | kelas_siswa, master_kelas | kelas_siswa, master_kelas | - | ret:getResult |
| `getAllKelasSort($tp = null, $smt = null, $level = null)` | 3 (C:Admin/JadwalController.php, C:Guru/JadwalController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getAllKelasByArrayId($tp, $smt, $arrId)` | 1 (C:Guru/JadwalController.php) | master_kelas | master_kelas | - | ret:getResult |
| `getJadwalKbm($tp, $smt)` | 2 (C:Admin/JadwalController.php, C:Guru/JadwalController.php) | kelas_jadwal_kbm | kelas_jadwal_kbm | - | ret:getResult |
| `decodeMapelKelasAssignments($raw): array` | 2 (C:Admin/JadwalController.php, C:DataguruController.php) | - | - | - | - |
| `getGuruAssignmentMap(int $idTp, int $idSmt): array` | 2 (C:Admin/JadwalController.php, C:Admin/JurnalController.php) | jabatan_guru, master_guru | jabatan_guru, master_guru | - | ret:getResult |
| `getGuruAssignedPairs(int $idGuru, int $idTp, int $idSmt): array` | 3 (C:Admin/JadwalController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php) | jabatan_guru | jabatan_guru | - | ret:getResult |
| `getUnassignedSchedulePairs(int $idTp, int $idSmt): array` | 1 (C:Admin/JadwalController.php) | jabatan_guru, kelas_jadwal_mapel, master_kelas, master_mapel | jabatan_guru, kelas_jadwal_mapel, master_kelas, master_mapel | - | ret:getResult |
| `getJadwalKbmGuruHariIni(int $idGuru, int $hari, int $idTp, int $idSmt): array` | 2 (C:Guru/AttendanceController.php, C:Guru/JurnalController.php) | kelas_jadwal_mapel, master_kelas, master_mapel | kelas_jadwal_mapel, master_kelas, master_mapel | - | ret:getResult |
| `getJadwalHarian($tp, $smt, $hari, $kelas = null)` | 3 (C:Admin/JadwalController.php, C:Guru/JadwalController.php, C:Guru/PiketController.php) | kelas_jadwal_mapel, master_mapel | kelas_jadwal_mapel, master_mapel | - | ret:getResult |
| `getAllJadwalHarian($tp, $smt)` | 0 | kelas_jadwal_mapel | kelas_jadwal_mapel | - | ret:getResult |
| `insertBatchKbm($data)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_kbm | kelas_jadwal_kbm | - | - |
| `updateBatchKbm($data)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_kbm | kelas_jadwal_kbm | - | - |
| `insertBatchJadwal($data)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_mapel | kelas_jadwal_mapel | - | - |
| `updateBatchJadwal($data)` | 0 | kelas_jadwal_mapel | kelas_jadwal_mapel | - | - |
| `deleteJadwalByIds($ids)` | 0 | kelas_jadwal_mapel | kelas_jadwal_mapel | - | - |
| `deleteJadwalMapel($id)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_mapel | kelas_jadwal_mapel | - | - |
| `deleteJadwalHarian($tp, $smt, $hari = null, $includeMapel = false)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_mapel | kelas_jadwal_mapel | - | - |
| `getTpByTahun($tahun)` | 1 (C:Admin/JadwalController.php) | master_tp | master_tp | - | ret:getRow |
| `getAllJadwalKbmFull($tp, $smt)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_kbm, master_kelas | kelas_jadwal_kbm, master_kelas | - | ret:getResult |
| `getAllJadwalHarianFull($tp, $smt)` | 1 (C:Admin/JadwalController.php) | kelas_jadwal_mapel, master_kelas | kelas_jadwal_mapel, master_kelas | - | ret:getResult |

## AuthModel — 23 method (dipanggil 8, terhalang tabel 21)

Tabel model: `users`. File CI4: `app/Models/AuthModel.php`. File Laravel: `app/Models/AuthModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `loggedIn(): bool` | 0 | is_login | is_login | - | - |
| `inGroup(string\|array $group): bool` | 7 (C:BaseController.php, C:Guru/AttendanceController.php, C:Guru/KalenderController.php, C:Guru/LemburController.php, C:Guru/PiketController.php, C:Guru/PresensiSiswaQrController.php, C:GuruController.php) | role_name | role_name | - | - |
| `isAdmin(): bool` | 26 (C:Admin/JadwalController.php, C:Admin/JurnalController.php, C:Admin/KalenderController.php, C:Admin/KartuGuruController.php, C:Admin/KartuSiswaController.php, C:Admin/LemburController.php, C:Admin/PoinSiswaController.php, C:Admin/PresensiAdminController.php, C:Admin/PresensiBdrController.php, C:Admin/RewardController.php, C:Admin/UserManagementController.php, C:DataguruController.php, C:DatajurusanController.php, C:DatakelasController.php, C:DatasiswaController.php, C:DatatahunController.php, C:Guru/AttendanceController.php, C:Guru/JurnalController.php, C:Guru/PresensiSiswaQrController.php, C:PresensiSiswaController.php, C:ProfilsekolahController.php, C:SettingsController.php, C:UseradminController.php, C:UserguruController.php, C:UserpanitiaController.php, C:UsersiswaController.php) | role, role_name | role, role_name | - | - |
| `user(?int $id = null): ?object` | 3 (C:DashboardController.php, C:DatatahunController.php, C:Guru/AttendanceController.php) | user_id, users | user_id, users | BaseController::authUser | - |
| `userProfile(int $userId): ?object` | 0 | users | users | - | - |
| `allProfiles(array $userIds): array` | 0 | users | users | - | - |
| `register(array $data, string $group): int` | 0 | users, users_groups | users, users_groups | BaseController::authRegister | - |
| `isExist(string $username): ?object` | 0 | users | users | BaseController::authIsExist | - |
| `renameUser(int $userId, string $newUsername): array` | 1 (C:GuruController.php) | master_guru, master_siswa, users | master_guru, master_siswa, users | BaseController::authRenameUser | ret:getRow,countAllResults,affectedRows |
| `registerAll(array $users, string $group): array` | 0 | - | - | - | - |
| `login(string $username): ?object` | 2 (C:AuthController.php, C:Guru/AttendanceController.php) | groups, users, users_groups | groups, users, users_groups | - | ret:getRow |
| `errorLogin(array $data): bool` | 0 | login_attempts | login_attempts | - | - |
| `cekErrorLogin(string $username, ?int $dalamDetik = null): array` | 1 (C:AuthController.php) | login_attempts | login_attempts | - | ret:getResult |
| `resetErrorLogin(string $username): bool` | 1 (C:AuthController.php) | login_attempts | login_attempts | BaseController::authResetErrorLogin | - |
| `isAktif(string $username): ?object` | 0 | buku_induk, master_siswa | buku_induk, master_siswa | - | ret:getRow |
| `usernameCheck(string $username, ?int $id = null): bool` | 2 (C:UserguruController.php, C:UsersiswaController.php) | users | users | - | - |
| `changeUser(int $id, array $userData): bool` | 0 | users | users | - | - |
| `deleteUser(int $id): bool` | 0 | master_guru, users, users_groups | master_guru, users, users_groups | BaseController::authDeleteUser | ret:getRow |
| `groups(): array` | 0 | users_groups | users_groups | BaseController::authGroups | ret:getResult |
| `getUsersGroups(int $userId): array` | 0 | groups, users_groups | groups, users_groups | - | ret:getResult |
| `checkLevel(string $username): ?object` | 0 | users, users_groups | users, users_groups | - | ret:getRow |
| `changePassword(string $username, string $oldPassword, string $newPassword): bool` | 0 | users | users | BaseController::authChangePassword | - |
| `change_password(string $username, string $oldPassword, string $newPassword): bool` | 0 | - | - | BaseController::authChangePassword | - |

## DashboardModel — 21 method (dipanggil 10, terhalang tabel 16)

Tabel model: `-`. File CI4: `app/Models/DashboardModel.php`. File Laravel: `app/Models/DashboardModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getSetting(): ?object` | 4 (C:BaseController.php, L:SyncService.php, S:IzinKuotaService.php, S:RekapPdfService.php) | setting | setting | BaseController::getSettingRow | ret:getRow |
| `getTahunActive(): ?object` | 2 (S:CbtService.php, S:PresensiBdrService.php) | master_tp | master_tp | - | ret:getRow |
| `getSemesterActive(): ?object` | 2 (S:CbtService.php, S:PresensiBdrService.php) | master_smt | master_smt | - | ret:getRow |
| `getTahun(): array` | 0 | master_tp | master_tp | - | ret:getResult |
| `getSemester(): array` | 0 | master_smt | master_smt | - | ret:getResult |
| `total(string $table, ?string $where = null): int` | 2 (C:DashboardController.php, C:DatamapelController.php) | - | - | - | ret:countAllResults |
| `getTotalSiswa(int $tp, int $smt): int` | 1 (C:DashboardController.php) | buku_induk, kelas_siswa | buku_induk, kelas_siswa | - | ret:countAllResults |
| `totalWaliKelas(int $tp, int $smt): int` | 0 | master_kelas | master_kelas | - | ret:countAllResults |
| `totalJadwal(int $tp, int $smt): int` | 0 | - | - | - | - |
| `getDataSiswa(string $username, int $tp, int $smt): ?object` | 2 (C:DashboardController.php, S:CbtService.php) | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | - | ret:getRow |
| `getDataGuruByUserId(int $userId, int $tp, int $smt): ?object` | 4 (C:Guru/AbsensiController.php, C:Guru/JadwalController.php, C:Guru/PiketController.php, C:GuruController.php) | master_guru | master_guru | - | ret:getRow |
| `getDataPanitiaByUserId(int $userId, int $tp, int $smt): ?object` | 1 (C:UsersiswaController.php) | master_panitia, users | master_panitia, users | - | ret:getRow |
| `getDataGuruByUsername(string $username, int $tp, int $smt): ?object` | 0 | jabatan_guru, level_kelas, master_guru, master_kelas | jabatan_guru, level_kelas, master_guru, master_kelas | - | ret:getRow |
| `getDataGuruById(int $id_guru, int $tp, int $smt): ?object` | 0 | jabatan_guru, level_kelas, master_guru, master_kelas | jabatan_guru, level_kelas, master_guru, master_kelas | - | ret:getRow |
| `getRunningText(): array` | 0 | running_text | running_text | - | ret:getResult |
| `updateData(string $table, $data, string $pk, $id = null, bool $batch = false): bool` | 0 | - | - | - | - |
| `hapus(string $table, $ids, string $pk): bool` | 3 (C:DatakelasController.php, C:DatasiswaController.php, C:DatatahunController.php) | - | - | - | - |
| `getDataTahun(): array` | 0 | master_tp | master_tp | - | ret:getResult |
| `getTahunByTahun($tahun)` | 0 | master_tp | master_tp | - | ret:getRow |
| `getTpBefore($tahun)` | 1 (C:DatakelasController.php) | - | - | - | - |
| `getSettingRapor()` | 0 | setting_rapor | setting_rapor | - | ret:getRow |

## JurnalGuruModel — 21 method (dipanggil 16, terhalang tabel 16)

Tabel model: `guru_jurnal`. File CI4: `app/Models/JurnalGuruModel.php`. File Laravel: `app/Models/JurnalGuruModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | guru_jurnal | guru_jurnal | - | - |
| `validasiStatusList(): array` | 0 | - | - | - | - |
| `detachStaleJadwalLinks(int $tp, int $smt): int` | 2 (C:Admin/JadwalController.php, C:CronControllerController.php) | guru_jurnal, kelas_jadwal_mapel | guru_jurnal, kelas_jadwal_mapel | - | RAW-SQL; ret:affectedRows |
| `setValidasi(int $idJurnal, string $status, int $olehUserId, ?string $catatan = null, array $dariStatus = []): array` | 1 (C:Guru/PiketController.php) | guru_jurnal | guru_jurnal | - | ret:affectedRows |
| `resetValidasi(int $idJurnal): void` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | - |
| `getJurnalGuru($id_guru, $tp, $smt, $bulan = null) {` | 0 | guru_jurnal, master_kelas, master_mapel | guru_jurnal, master_kelas, master_mapel | - | ret:getResult |
| `getJurnalById($id_jurnal)` | 1 (C:Guru/PiketController.php) | guru_jurnal, master_kelas, master_mapel | guru_jurnal, master_kelas, master_mapel | - | ret:getRow |
| `getRiwayatBulan(int $idGuru, int $idTp, int $idSmt, string $mulai, string $selesai, array $filter = [], bool $kronologis = false): array` | 1 (C:Guru/JurnalController.php) | guru_jurnal, master_kelas, master_mapel | guru_jurnal, master_kelas, master_mapel | - | ret:getResult |
| `getStatistik(int $idGuru, int $idTp, int $idSmt, string $mulai, string $selesai): object` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | RAW-SQL; ret:getRow |
| `getByIds(array $ids): array` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getResult |
| `lampirkanRingkasanAbsen(array $jurnals): void` | 1 (C:Guru/JurnalController.php) | master_siswa | master_siswa | - | ret:getResult |
| `getJurnalDetail(int $id): ?object` | 1 (C:Guru/JurnalController.php) | guru_jurnal, master_guru, master_kelas, master_mapel | guru_jurnal, master_guru, master_kelas, master_mapel | - | ret:getRow |
| `findMilikGuru(int $id, int $idGuru): ?object` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getRow |
| `findSumberSalin(int $id, int $idGuru, int $idTp, int $idSmt): ?object` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getRow |
| `jurnalTerakhir(int $idGuru, int $idTp, int $idSmt): ?object` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getRow |
| `adaJurnalGanda(int $idGuru, int $idTp, int $idSmt, string $tanggal, string $jamKe, int $idKelas, int $idMapel, int $abaikanId = 0): bool` | 1 (C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getResult |
| `getStatusJurnalHarian(int $idGuru, int $idTp, int $idSmt, int $hari, string $tanggal, bool $hanyaTervalidasi = false): array` | 2 (C:Guru/AttendanceController.php, C:Guru/JurnalController.php) | guru_jurnal | guru_jurnal | - | ret:getResult |
| `cocokkanJurnalKeSlot(array $slots, array $jurnal): array` | 0 | - | - | - | - |
| `normalizeJamKe($value): string` | 0 | - | - | - | - |
| `getSesiBelumDijurnal(int $idGuru, int $idTp, int $idSmt, int $hari, string $tanggal, string $batasWaktu = '', bool $hanyaTervalidasi = false): array` | 1 (S:WajibJurnalPulangService.php) | - | - | - | - |
| `slotSudahSelesai(string $dari, string $sampai, string $batas): bool` | 0 | - | - | - | - |

## UsersModel — 18 method (dipanggil 13, terhalang tabel 15)

Tabel model: `-`. File CI4: `app/Models/UsersModel.php`. File Laravel: `app/Models/UsersModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getDuplicateUsernames($table)` | 0 | - | - | - | ret:getResult |
| `usernameCheck($username, $table)` | 2 (C:UserguruController.php, C:UsersiswaController.php) | - | - | - | ret:getResult |
| `getDatausers($id = null)` | 0 | groups, users, users_groups | groups, users, users_groups | - | ret:getResult |
| `getLevelGuru()` | 1 (C:UserguruController.php) | level_guru | level_guru | - | ret:getResult |
| `getAllAdmin()` | 2 (C:Admin/UserManagementController.php, C:UseradminController.php) | groups, users, users_groups | groups, users, users_groups | - | ret:getResult |
| `getDataadmin()` | 0 | - | - | - | - |
| `getUserGuru($tp, $smt)` | 1 (C:UserguruController.php) | jabatan_guru, level_guru, master_guru, school_uuid, users | jabatan_guru, level_guru, master_guru, school_uuid, users | - | ret:getResult |
| `getDataGuru($id)` | 2 (C:DataguruController.php, C:UserguruController.php) | master_guru | master_guru | - | ret:getRow |
| `getDetailGuru($id)` | 1 (C:UserguruController.php) | jabatan_guru, level_guru, master_guru, users | jabatan_guru, level_guru, master_guru, users | - | ret:getRow |
| `getUsers($username)` | 1 (C:UserguruController.php) | users | users | - | ret:getRow |
| `getGuruByUsername($username)` | 1 (C:UserguruController.php) | master_guru | master_guru | - | ret:getRow |
| `getUserPanitia()` | 0 | master_panitia, school_uuid, users | master_panitia, school_uuid, users | - | ret:getResult,countAllResults |
| `getDataPanitia($id)` | 0 | master_panitia | master_panitia | - | ret:getRow |
| `getKelas($tp, $smt)` | 1 (C:UserguruController.php) | master_kelas, school_uuid | master_kelas, school_uuid | - | ret:getResult |
| `getMapel()` | 1 (C:UserguruController.php) | master_mapel, school_uuid | master_mapel, school_uuid | - | ret:getResult |
| `getUserSiswaPage($id_tp, $id_smt, $offset, $limit, $search = null, $sort = null, $order = null, $reset = false)` | 1 (C:UsersiswaController.php) | buku_induk, kelas_siswa, login_attempts, master_kelas, master_siswa, school_uuid, users | buku_induk, kelas_siswa, login_attempts, master_kelas, master_siswa, school_uuid, users | - | ret:getResult |
| `getUserSiswaTotalPage($search = null, $reset = false)` | 1 (C:UsersiswaController.php) | buku_induk, login_attempts, master_siswa, school_uuid | buku_induk, login_attempts, master_siswa, school_uuid | - | ret:countAllResults |
| `getUserSiswa($tp, $smt)` | 1 (C:UsersiswaController.php) | kelas_siswa, master_kelas, master_siswa, school_uuid, users | kelas_siswa, master_kelas, master_siswa, school_uuid, users | - | ret:getResult |

## PenilaianGuruModel — 17 method (dipanggil 2, terhalang tabel 11)

Tabel model: `-`. File CI4: `app/Models/PenilaianGuruModel.php`. File Laravel: `app/Models/PenilaianGuruModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getPeriodeList()` | 0 | master_smt, master_tp, penilaian_guru_periode | master_smt, master_tp, penilaian_guru_periode | - | ret:getResult |
| `getPeriode($id)` | 0 | penilaian_guru_periode | penilaian_guru_periode | - | ret:getRow |
| `savePeriode($data)` | 0 | penilaian_guru_periode | penilaian_guru_periode | - | - |
| `deletePeriode($id)` | 0 | penilaian_guru_periode, penilaian_guru_skor | penilaian_guru_periode, penilaian_guru_skor | - | - |
| `getSkorByPeriode($id_periode)` | 0 | master_guru, penilaian_guru_skor | master_guru, penilaian_guru_skor | - | ret:getResult |
| `getSkorGuru($id_periode, $id_guru)` | 0 | penilaian_guru_skor | penilaian_guru_skor | - | ret:getRow |
| `getSkorById($id)` | 0 | master_guru, penilaian_guru_periode, penilaian_guru_skor | master_guru, penilaian_guru_periode, penilaian_guru_skor | - | ret:getRow |
| `hitungSkor($id_periode)` | 0 | master_guru, penilaian_guru_skor | master_guru, penilaian_guru_skor | - | ret:getResult,getRow |
| `hitungKehadiran($id_guru, $tgl_mulai, $tgl_selesai, $holidays, $approvedLeaves)` | 0 | absensi_harian | - | - | ret:getResult |
| `hitungMengajar($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $scheduleMap)` | 0 | guru_jurnal | guru_jurnal | - | ret:getResult |
| `hitungPiket($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $piketMap)` | 0 | absensi_harian | - | - | ret:getRow |
| `getHolidays($tgl_mulai, $tgl_selesai)` | 1 (C:Guru/KalenderController.php) | absensi_hari_libur | - | - | ret:getResult |
| `getApprovedLeaves($tgl_mulai, $tgl_selesai)` | 0 | absensi_izin | - | - | ret:getResult |
| `getScheduleMap($id_tp, $id_smt)` | 0 | jabatan_guru, kelas_jadwal_mapel | jabatan_guru, kelas_jadwal_mapel | - | ret:getResult |
| `getPiketMap($id_tp, $id_smt)` | 0 | piket_jadwal | piket_jadwal | - | ret:getResult |
| `getPredikat($skor)` | 0 | - | - | - | - |

## UserNotifikasiModel — 17 method (dipanggil 16, terhalang tabel 2)

Tabel model: `user_notifikasi`. File CI4: `app/Models/UserNotifikasiModel.php`. File Laravel: `app/Models/UserNotifikasiModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getByUserId(int $userId): ?object` | 4 (C:Admin/PresensiAdminController.php, C:Guru/AttendanceController.php, L:TelegramLink.php, S:NotificationService.php) | user_notifikasi | - | - | - |
| `getByChatId(string $chatId): ?object` | 1 (C:Api/TelegramBotController.php) | user_notifikasi | - | - | - |
| `getByLinkToken(string $token): ?object` | 0 | user_notifikasi | - | - | - |
| `issueLinkToken(int $userId): ?string` | 1 (C:Guru/AttendanceController.php) | user_notifikasi | - | - | - |
| `bindChat(string $token, string $chatId): ?int` | 1 (C:Api/TelegramBotController.php) | user_notifikasi | - | - | - |
| `bindChatDirect(int $userId, string $chatId): bool` | 1 (L:TelegramLink.php) | user_notifikasi | - | - | - |
| `unbindByChatId(string $chatId): bool` | 3 (C:Admin/PresensiAdminController.php, C:Api/TelegramBotController.php, L:TelegramLink.php) | user_notifikasi | - | - | - |
| `getPrefs(int $userId): array` | 2 (C:Guru/AttendanceController.php, S:NotificationService.php) | - | - | - | - |
| `setPrefs(int $userId, array $prefs): bool` | 1 (C:Guru/AttendanceController.php) | user_notifikasi | - | - | - |
| `getAllLinked(): array` | 1 (C:Admin/PresensiAdminController.php) | master_guru, user_notifikasi, users | master_guru, users | - | ret:getResult |
| `getVerifiedWaNumber(int $userId): string` | 4 (C:DataguruController.php, C:Guru/AttendanceController.php, L:WhatsAppLink.php, S:NotificationService.php) | user_notifikasi | - | - | - |
| `getWaNumber(int $userId): string` | 2 (C:Admin/PresensiAdminController.php, L:WhatsAppLink.php) | - | - | - | - |
| `setWaNumber(int $userId, string $number, bool $verified = false): bool` | 2 (Co:Commands/WaBulkLinkCommand.php, L:WhatsAppLink.php) | user_notifikasi | - | - | - |
| `verifyWaNumber(int $userId): bool` | 1 (C:Admin/PresensiAdminController.php) | user_notifikasi | - | - | - |
| `unbindWa(int $userId): bool` | 2 (C:Admin/PresensiAdminController.php, L:WhatsAppLink.php) | user_notifikasi | - | - | - |
| `getAllWaLinked(): array` | 1 (C:Admin/PresensiAdminController.php) | master_guru, user_notifikasi, users | master_guru, users | - | ret:getResult |
| `countVerifiedWa(): int` | 1 (C:Admin/PresensiAdminController.php) | user_notifikasi | - | - | ret:countAllResults |

## DropdownModel — 15 method (dipanggil 11, terhalang tabel 12)

Tabel model: `-`. File CI4: `app/Models/DropdownModel.php`. File Laravel: `app/Models/DropdownModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getBulan()` | 0 | bulan | bulan | - | ret:getResult |
| `getAllSesi()` | 0 | - | - | - | - |
| `getAllRuang()` | 1 (C:UserpanitiaController.php) | - | - | - | - |
| `getDataKelompokMapel()` | 1 (C:DatamapelController.php) | master_kelompok_mapel | master_kelompok_mapel | - | ret:getResult |
| `getAllMapel()` | 3 (C:Admin/JadwalController.php, C:DataguruController.php, C:DatajurusanController.php) | master_mapel | master_mapel | - | ret:getResult |
| `getAllKelas($tp, $smt, $level = null)` | 9 (C:Admin/JadwalController.php, C:Admin/PoinSiswaController.php, C:Admin/PresensiBdrController.php, C:DashboardController.php, C:DataguruController.php, C:DatasiswaController.php, C:Guru/PiketController.php, C:Guru/PoinSiswaController.php, C:PresensiSiswaController.php) | master_kelas | master_kelas | - | ret:getResult |
| `getAllKelasByArrayId($tp, $smt, $arrId)` | 1 (C:Guru/JadwalController.php) | master_kelas | master_kelas | - | ret:getResult |
| `getAllGuru()` | 4 (C:Admin/JadwalController.php, C:DatakelasController.php, C:Guru/PiketController.php, C:UserpanitiaController.php) | master_guru, users | master_guru, users | - | ret:getResult |
| `getAllLevelGuru()` | 1 (C:DataguruController.php) | level_guru | level_guru | - | ret:getResult |
| `getAllEkskul()` | 1 (C:DataguruController.php) | master_ekstra | master_ekstra | - | ret:getResult |
| `getAllKodeEkskul()` | 1 (C:DataguruController.php) | master_ekstra | master_ekstra | - | ret:getResult |
| `getDataJurusan()` | 1 (C:DatakelasController.php) | master_jurusan | master_jurusan | - | ret:getResult |
| `getAllLevelKelas($jenjang)` | 2 (C:Admin/PresensiBdrController.php, C:DatakelasController.php) | - | - | - | - |
| `getNamaKelasById($tp, $smt, $id_kelas)` | 0 | master_kelas | master_kelas | - | ret:getRow |
| `getAllPanitiaPengawas()` | 0 | master_panitia | master_panitia | - | ret:getResult |

## PoinSiswaModel — 15 method (dipanggil 12, terhalang tabel 5)

Tabel model: `poin_siswa_history`. File CI4: `app/Models/PoinSiswaModel.php`. File Laravel: `app/Models/PoinSiswaModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `ensureTables(): void` | 4 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Siswa/PresensiController.php, S:SiswaPoinService.php) | - | - | - | RAW-SQL |
| `getSkor(int $idSiswa): ?int` | 4 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Siswa/PresensiController.php, S:SiswaPoinService.php) | poin_siswa_history | - | - | ret:getRow |
| `kunciSiswa(object $db, int $idSiswa): void` | 0 | - | - | - | RAW-SQL |
| `kelasAktifSiswa(int $idSiswa): ?int` | 0 | kelas_siswa | kelas_siswa | - | ret:getRowArray,getRow |
| `updateCache(int $idSiswa): ?int` | 1 (S:SiswaPoinService.php) | master_siswa | master_siswa | - | - |
| `getRiwayat(int $idSiswa, int $limit = 50): array` | 5 (C:Admin/LemburController.php, C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php, C:Siswa/PresensiController.php) | disiplin_kategori, poin_siswa_history | - | - | ret:getResult |
| `getLeaderboard(?int $idKelas = null, int $limit = 200): array` | 3 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php) | buku_induk, kelas_siswa, master_siswa, poin_siswa_history | buku_induk, kelas_siswa, master_siswa | - | ret:getResult |
| `getMonthly(int $idSiswa, int $tahun): array` | 1 (C:Admin/PoinSiswaController.php) | poin_siswa_history | - | - | ret:getResult |
| `getTypeSummary(int $idSiswa, int $tahun): array` | 1 (C:Admin/PoinSiswaController.php) | poin_siswa_history | - | - | ret:getResult |
| `getKategori(bool $activeOnly = true): array` | 2 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php) | disiplin_kategori | - | - | ret:getResult |
| `saveKategori(array $data, ?int $id = null): array` | 1 (C:Admin/PoinSiswaController.php) | disiplin_kategori, gagal | gagal | - | ret:countAllResults |
| `deleteKategori(int $id): array` | 1 (C:Admin/PoinSiswaController.php) | disiplin_kategori, poin_siswa_history | - | - | ret:countAllResults |
| `recordManual(int $idSiswa, int $kategoriId, string $keterangan, ?int $createdBy = null): array` | 2 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php) | buku_induk, disiplin_kategori, master_siswa, poin_siswa_history | buku_induk, master_siswa | - | ret:getRowArray,getRow |
| `syncPresensi(object $row): array` | 1 (C:Admin/PoinSiswaController.php) | - | - | - | - |
| `seedDefaultKategori(): void` | 0 | disiplin_kategori | - | - | ret:countAllResults |

## PoinHistoryModel — 12 method (dipanggil 8, terhalang tabel 2)

Tabel model: `poin_history`. File CI4: `app/Models/PoinHistoryModel.php`. File Laravel: `app/Models/PoinHistoryModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `ensureTable()` | 0 | - | - | - | - |
| `safeQuery(callable $fn, $default = null)` | 0 | - | - | - | - |
| `getSaldo($userId)` | 3 (C:Admin/PresensiAdminController.php, C:Admin/RewardController.php, C:Guru/RewardController.php) | poin_history | - | - | ret:getRow |
| `updateTotalPoin($userId, ?int $tahun = null)` | 4 (C:Admin/LemburController.php, C:Admin/PresensiAdminController.php, C:Admin/RewardController.php, C:Guru/RewardController.php) | master_guru, poin_history | master_guru | - | ret:getRow |
| `syncAttendancePoint($attendanceId)` | 4 (C:Admin/PresensiAdminController.php, C:CronControllerController.php, C:Guru/AttendanceController.php, C:Guru/PresensiSiswaQrController.php) | absensi_harian, poin_history | - | - | ret:getRow |
| `getRiwayat($userId, $limit = 20)` | 5 (C:Admin/LemburController.php, C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php, C:Siswa/PresensiController.php) | poin_history | - | - | ret:getResult |
| `getRiwayatBulanan($userId, $bulan, $tahun)` | 0 | poin_history | - | - | ret:getResult |
| `getLeaderboard($bulan, $tahun, $limit = 20)` | 3 (C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php) | absensi_harian, master_guru, users, users_groups | master_guru, users, users_groups | - | ret:getResult |
| `seedFromAttendance($userId, $bulan, $tahun)` | 2 (C:Admin/PoinGuruController.php, C:Admin/RewardController.php) | absensi_harian, absensi_setting, poin_history | - | - | ret:getResult,getRow |
| `estimateCheckoutPoints($minutes, $setting, $isPenalty)` | 0 | - | - | - | - |
| `calculateMonthlyScore($userId, $bulan, $tahun)` | 1 (C:Guru/RewardController.php) | absensi_harian | - | - | ret:getRow |
| `syncMonthlyScoreBonus($userId, $bulan, $tahun)` | 1 (C:Guru/RewardController.php) | poin_history | - | - | ret:getRow |

## SiswaPresensiModel — 8 method (dipanggil 6, terhalang tabel 8)

Tabel model: `presensi_siswa`. File CI4: `app/Models/SiswaPresensiModel.php`. File Laravel: `app/Models/SiswaPresensiModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `filterSiswaAktif($builder, string $aliasMs = 'ms'): void` | 1 (C:Admin/KartuSiswaController.php) | buku_induk | buku_induk | - | - |
| `getSiswaByKelas(int $idKelas, int $idTp, int $idSmt): array` | 2 (C:Guru/JurnalController.php, C:PresensiSiswaController.php) | kelas_siswa, master_siswa | kelas_siswa, master_siswa | - | ret:getResult |
| `getMapelByKelas(int $idKelas, int $idTp, int $idSmt): array` | 0 | kelas_jadwal_mapel, master_mapel | kelas_jadwal_mapel, master_mapel | - | ret:getResult |
| `getPresensiSiswa( int $idKelas, ?int $idMapel, string $tgl ): array {` | 1 (C:PresensiSiswaController.php) | master_siswa, presensi_siswa | master_siswa | - | ret:getResult |
| `getRekapBulanan(int $idKelas, string $bulan, string $tahun): array` | 1 (C:PresensiSiswaController.php) | master_siswa, presensi_siswa | master_siswa | - | ret:getResult |
| `getMonitoringHarian(int $idKelas, string $tgl, ?int $idTp, ?int $idSmt): array` | 1 (C:PresensiSiswaController.php) | kelas_siswa, master_guru, master_siswa, presensi_siswa | kelas_siswa, master_guru, master_siswa | - | ret:getResult |
| `getStatHarian(int $idKelas, string $tgl): object` | 1 (C:PresensiSiswaController.php) | master_siswa, presensi_siswa | master_siswa | - | ret:getRow |
| `getRiwayatGuru(int $idGuru, int $limit = 20): array` | 0 | master_kelas, master_mapel, presensi_siswa | master_kelas, master_mapel | - | ret:getResult |

## LogModel — 7 method (dipanggil 2, terhalang tabel 6)

Tabel model: `logs`. File CI4: `app/Models/LogModel.php`. File Laravel: `app/Models/LogModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `_ensureTableExists(): void` | 0 | - | - | - | - |
| `saveLog(int $aktivitas, string $keterangan = '', ?int $userId = null): bool` | 0 | logs, user_id | logs, user_id | BaseController::saveAuditLog | - |
| `getRecentLogs(int $limit = 50): array` | 0 | logs | logs | - | - |
| `getLogsByUser(int $userId, int $limit = 50): array` | 0 | logs | logs | - | - |
| `hapusLog(int $idLog): bool` | 0 | logs | logs | - | - |
| `getLog($offset, $limit, $search = null, $filters = null)` | 1 (C:DashboardController.php) | logs, users | logs, users | - | ret:getResult |
| `getCountLog($search = null, $filters = null)` | 1 (C:DashboardController.php) | logs, users | logs, users | - | ret:countAllResults |

## NilaiModel — 7 method (dipanggil 1, terhalang tabel 6)

Tabel model: `-`. File CI4: `app/Models/NilaiModel.php`. File Laravel: `app/Models/NilaiModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | - | - | - | - |
| `getNilaiProses($id_siswa, $id_mapel, $id_tp, $tipe = 'sumatif_lm')` | 0 | nilai_proses | nilai_proses | - | ret:getRow |
| `getAllNilaiProsesKelas($id_kelas, $id_mapel, $id_tp, $tipe = 'sumatif_lm')` | 0 | kelas_siswa, nilai_proses | kelas_siswa, nilai_proses | - | ret:getResult |
| `saveNilaiProses($data)` | 0 | nilai_proses | nilai_proses | - | - |
| `getNilaiAkhir($id_siswa, $id_mapel, $id_tahun, $id_smt)` | 0 | nilai_akhir_kurmer | nilai_akhir_kurmer | - | ret:getRow |
| `saveNilaiAkhir($data)` | 0 | nilai_akhir_kurmer | nilai_akhir_kurmer | - | - |
| `getRaporSiswa($id_siswa, $id_tahun, $id_smt)` | 0 | master_mapel, master_mapel_kelompok, nilai_akhir_kurmer | master_mapel, master_mapel_kelompok, nilai_akhir_kurmer | - | ret:getResult |

## SettingsModel — 7 method (dipanggil 1, terhalang tabel 5)

Tabel model: `setting`. File CI4: `app/Models/SettingsModel.php`. File Laravel: `app/Models/SettingsModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getSetting(): ?object` | 4 (C:BaseController.php, L:SyncService.php, S:IzinKuotaService.php, S:RekapPdfService.php) | setting | setting | BaseController::getSettingRow | - |
| `updateSetting(array $data): bool` | 0 | setting | setting | BaseController::updateSettingRow | - |
| `getActiveTp(): ?int` | 0 | setting | setting | - | - |
| `getActiveSmt(): ?int` | 0 | setting | setting | - | - |
| `truncate($tables)` | 0 | users, users_groups | users, users_groups | - | COLLISION; RAW-SQL; ret:getResult |
| `rowSize($table)` | 0 | - | - | - | ret:countAllResults |
| `toJSON($table)` | 0 | - | - | - | ret:getResult |

## KalenderModel — 6 method (dipanggil 4, terhalang tabel 0)

Tabel model: `kalender_akademik`. File CI4: `app/Models/KalenderModel.php`. File Laravel: `app/Models/KalenderModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getEvents($bulan, $tahun)` | 1 (C:Guru/KalenderController.php) | - | - | - | - |
| `getEventsByRange($tglAwal, $tglAkhir)` | 1 (C:Admin/KalenderController.php) | kalender_akademik | - | - | ret:getResult |
| `getHolidaysByRange($tglAwal, $tglAkhir)` | 1 (C:Admin/KalenderController.php) | absensi_hari_libur | - | - | ret:getResult |
| `getUpcoming($limit = 5)` | 0 | kalender_akademik | - | - | ret:getResult |
| `getHolidays($bulan, $tahun)` | 1 (C:Guru/KalenderController.php) | absensi_hari_libur, kalender_akademik | - | - | ret:getResult |
| `getEvent($id)` | 0 | kalender_akademik | - | - | - |

## GuruJadwalPribadiModel — 5 method (dipanggil 5, terhalang tabel 5)

Tabel model: `guru_jadwal_pribadi`. File CI4: `app/Models/GuruJadwalPribadiModel.php`. File Laravel: `app/Models/GuruJadwalPribadiModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | CURRENT_TIMESTAMP, guru_jadwal_pribadi | CURRENT_TIMESTAMP, guru_jadwal_pribadi | - | - |
| `getByGuru(int $idGuru, int $idTp, int $idSmt): array` | 1 (C:Guru/JadwalController.php) | guru_jadwal_pribadi, master_kelas, master_mapel | guru_jadwal_pribadi, master_kelas, master_mapel | - | ret:getResult |
| `getByGuruAndHari(int $idGuru, int $idTp, int $idSmt, int $hari): array` | 1 (C:Guru/JadwalController.php) | guru_jadwal_pribadi, master_kelas, master_mapel | guru_jadwal_pribadi, master_kelas, master_mapel | - | ret:getResult |
| `findConflictsForGuru(int $idGuru, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array` | 1 (C:Guru/JadwalController.php) | guru_jadwal_pribadi | guru_jadwal_pribadi | - | ret:getResult |
| `findConflictsForKelas(int $idKelas, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array` | 1 (C:Guru/JadwalController.php) | guru_jadwal_pribadi | guru_jadwal_pribadi | - | ret:getResult |

## JabatanGuruModel — 5 method (dipanggil 4, terhalang tabel 4)

Tabel model: `jabatan_guru`. File CI4: `app/Models/JabatanGuruModel.php`. File Laravel: `app/Models/JabatanGuruModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `makeKey(int $idGuru, int $idTp, int $idSmt): string` | 0 | - | - | - | - |
| `findForGuru(int $idGuru, int $idTp, int $idSmt): ?object` | 3 (C:Admin/JadwalController.php, C:DataguruController.php, C:DatakelasController.php) | jabatan_guru | jabatan_guru | - | ret:getRow |
| `upsert(int $idGuru, int $idTp, int $idSmt, array $data): bool` | 5 (C:Admin/JadwalController.php, C:DataguruController.php, C:DatakelasController.php, L:SyncService.php, S:DapodikSyncService.php) | jabatan_guru | jabatan_guru | - | - |
| `releaseWaliKelas(int $idTp, int $idSmt, int $idKelas, ?int $keepGuru = null): void` | 3 (C:DataguruController.php, C:DatakelasController.php, S:DapodikSyncService.php) | jabatan_guru | jabatan_guru | - | - |
| `syncWaliToMasterKelas(int $idGuru, int $idTp, int $idSmt, int $idKelas): void` | 3 (C:DataguruController.php, C:DatakelasController.php, S:DapodikSyncService.php) | master_kelas | master_kelas | - | - |

## NotifChannelConfigModel — 5 method (dipanggil 2, terhalang tabel 0)

Tabel model: `notif_channel_config`. File CI4: `app/Models/NotifChannelConfigModel.php`. File Laravel: `app/Models/NotifChannelConfigModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getConfig(): array` | 1 (C:Admin/PresensiAdminController.php) | notif_channel_config | - | - | - |
| `isEnabled(string $jenis, string $channel): bool` | 0 | - | - | - | - |
| `saveConfig(array $data): bool` | 1 (C:Admin/PresensiAdminController.php) | notif_channel_config | - | - | - |
| `seedMissing(array $existing): void` | 0 | notif_channel_config | - | - | - |
| `buildDefaults(): array` | 0 | - | - | - | - |

## PiketJadwalModel — 5 method (dipanggil 5, terhalang tabel 4)

Tabel model: `piket_jadwal`. File CI4: `app/Models/PiketJadwalModel.php`. File Laravel: `app/Models/PiketJadwalModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | CURRENT_TIMESTAMP | CURRENT_TIMESTAMP | - | - |
| `getJadwalByTpSmt($id_tp, $id_smt)` | 1 (C:Admin/PiketController.php) | master_guru, piket_jadwal | master_guru, piket_jadwal | - | ret:getResult |
| `getPiketHariIni($hari, $id_tp, $id_smt)` | 4 (C:Admin/PiketController.php, C:CronControllerController.php, C:Guru/PiketController.php, S:WajibJurnalPulangService.php) | master_guru, piket_jadwal | master_guru, piket_jadwal | - | ret:getResult |
| `getJadwalByGuru($id_guru, $id_tp, $id_smt)` | 1 (C:Guru/PiketController.php) | piket_jadwal | piket_jadwal | - | ret:getResult |
| `getJadwalMingguan($id_tp, $id_smt)` | 1 (C:Guru/PiketController.php) | - | - | - | - |

## LeaveModel — 4 method (dipanggil 1, terhalang tabel 3)

Tabel model: `absensi_izin`. File CI4: `app/Models/LeaveModel.php`. File Laravel: `app/Models/LeaveModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `statusSet(string $status): array` | 0 | - | - | - | - |
| `getPending(): array` | 2 (C:Admin/LemburController.php, C:Admin/RewardController.php) | absensi_izin, master_guru, users | master_guru, users | - | ret:getResult |
| `getAll( string\|int $bulan = '', string\|int $tahun = '', string $status = '', string $jenis = '' ): array {` | 0 | absensi_izin, master_guru, users | master_guru, users | - | ret:getResult |
| `getWithGuru(int $id): ?object` | 0 | absensi_izin, master_guru, users | master_guru, users | - | ret:getRow |

## LemburModel — 4 method (dipanggil 4, terhalang tabel 3)

Tabel model: `presensi_lembur`. File CI4: `app/Models/LemburModel.php`. File Laravel: `app/Models/LemburModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getLemburUser($userId, $limit = 20)` | 1 (C:Guru/LemburController.php) | presensi_lembur | - | - | ret:getResult |
| `getPending()` | 2 (C:Admin/LemburController.php, C:Admin/RewardController.php) | master_guru, presensi_lembur, users | master_guru, users | - | ret:getResult |
| `getRekap($bulan, $tahun)` | 1 (C:Admin/LemburController.php) | master_guru, presensi_lembur, users | master_guru, users | - | ret:getResult |
| `getRiwayat($bulan, $tahun, $limit = 200)` | 5 (C:Admin/LemburController.php, C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php, C:Siswa/PresensiController.php) | master_guru, presensi_lembur, users | master_guru, users | - | ret:getResult |

## PanitiaModel — 4 method (dipanggil 2, terhalang tabel 4)

Tabel model: `master_panitia`. File CI4: `app/Models/PanitiaModel.php`. File Laravel: `app/Models/PanitiaModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `get_all()` | 0 | master_panitia, school_uuid, users | master_panitia, school_uuid, users | - | ret:getResult |
| `get_by_id($id)` | 1 (C:UserpanitiaController.php) | master_panitia, users | master_panitia, users | - | ret:getRow |
| `get_by_username($username)` | 0 | master_panitia | master_panitia | - | - |
| `create($data)` | 5 (C:DataguruController.php, C:DatakelasController.php, C:DatamapelController.php, C:DatasiswaController.php, C:UserpanitiaController.php) | master_panitia | master_panitia | - | COLLISION |

## PostModel — 4 method (dipanggil 1, terhalang tabel 4)

Tabel model: `post`. File CI4: `app/Models/PostModel.php`. File Laravel: `app/Models/PostModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getPostUser($id_user)` | 0 | master_guru, master_panitia, post | master_guru, master_panitia, post | - | ret:getResult |
| `getPostForUser($kepada, $kelas = null, $limit = null, $offset = null)` | 1 (C:DashboardController.php) | master_guru, post | master_guru, post | - | ret:getResult |
| `getIdComments($id_post)` | 0 | post_comments | post_comments | - | ret:getResult |
| `getIdReplies($id_comment)` | 0 | post_reply | post_reply | - | ret:getResult |

## PresensiBdrModel — 4 method (dipanggil 4, terhalang tabel 1)

Tabel model: `presensi_bdr`. File CI4: `app/Models/PresensiBdrModel.php`. File Laravel: `app/Models/PresensiBdrModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getRowsUntukTanggal(string $tgl): array` | 2 (C:Admin/PresensiBdrController.php, S:PresensiBdrService.php) | presensi_bdr | - | - | ret:getResult |
| `getRowsDalamRentang(string $tglAwal, string $tglAkhir): array` | 1 (S:PresensiBdrService.php) | presensi_bdr | - | - | ret:getResult |
| `getDaftar(int $limit = 200, ?string $sejak = null): array` | 1 (C:Admin/PresensiBdrController.php) | level_kelas, master_kelas, presensi_bdr | level_kelas, master_kelas | - | ret:getResult |
| `adaBentrok(string $lingkup, ?int $idKelas, ?int $levelId, string $tglMulai, ?string $tglSelesai, int $kecualikanId = 0): bool` | 1 (C:Admin/PresensiBdrController.php) | presensi_bdr | - | - | ret:countAllResults |

## AttendanceLocationModel — 3 method (dipanggil 3, terhalang tabel 0)

Tabel model: `absensi_lokasi`. File CI4: `app/Models/AttendanceLocationModel.php`. File Laravel: `app/Models/AttendanceLocationModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getUtama(): ?object` | 1 (C:Admin/PresensiAdminController.php) | absensi_lokasi | - | - | - |
| `setUtama($id): ?object` | 1 (C:Admin/PresensiAdminController.php) | absensi_lokasi | - | - | - |
| `ensureAdaUtama(): void` | 1 (C:Admin/PresensiAdminController.php) | absensi_lokasi | - | - | - |

## AttendanceCorrectionModel — 2 method (dipanggil 2, terhalang tabel 2)

Tabel model: `absensi_koreksi`. File CI4: `app/Models/AttendanceCorrectionModel.php`. File Laravel: `app/Models/AttendanceCorrectionModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getPendingWithDetails()` | 1 (C:Admin/PresensiAdminController.php) | absensi_harian, absensi_koreksi, master_guru, users | master_guru, users | - | - |
| `getHistoryWithDetails($limit = 100)` | 1 (C:Admin/PresensiAdminController.php) | absensi_harian, absensi_koreksi, master_guru, users | master_guru, users | - | - |

## RewardRedemptionModel — 2 method (dipanggil 2, terhalang tabel 1)

Tabel model: `reward_redemptions`. File CI4: `app/Models/RewardRedemptionModel.php`. File Laravel: `app/Models/RewardRedemptionModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getRiwayat($userId, $limit = 20)` | 5 (C:Admin/LemburController.php, C:Admin/PoinSiswaController.php, C:Guru/PoinSiswaController.php, C:Guru/RewardController.php, C:Siswa/PresensiController.php) | reward_redemptions, rewards | - | - | ret:getResult |
| `getPending()` | 2 (C:Admin/LemburController.php, C:Admin/RewardController.php) | reward_redemptions, rewards, users | users | - | ret:getResult |

## ShiftModel — 2 method (dipanggil 1, terhalang tabel 0)

Tabel model: `absensi_shift`. File CI4: `app/Models/ShiftModel.php`. File Laravel: `app/Models/ShiftModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getShiftForUser(int $userId, int $dayIso): ?object` | 0 | absensi_shift, absensi_shift_anggota | - | - | ret:getRow |
| `getScheduleForUser(int $userId, int $dayIso, ?object $jadwalHarian, object $setting): array` | 5 (C:Admin/PresensiAdminController.php, C:CronControllerController.php, C:Guru/AttendanceController.php, C:Guru/PresensiSiswaQrController.php, S:AttendancePolicy.php) | - | - | - | - |

## TujuanPembelajaranModel — 2 method (dipanggil 1, terhalang tabel 2)

Tabel model: `e_tujuan_pembelajaran`. File CI4: `app/Models/TujuanPembelajaranModel.php`. File Laravel: `app/Models/TujuanPembelajaranModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `initialize()` | 5 (C:Admin/JurnalController.php, C:Admin/PiketController.php, C:Guru/JadwalController.php, C:Guru/JurnalController.php, C:Guru/PiketController.php) | e_tujuan_pembelajaran | e_tujuan_pembelajaran | - | - |
| `getTujuanByGuru($id_guru, $id_tp, $id_smt = null, $id_mapel = null, array $filters = [])` | 0 | e_tujuan_pembelajaran, master_guru, master_mapel | e_tujuan_pembelajaran, master_guru, master_mapel | - | ret:getResult |

## AttendanceModel — 1 method (dipanggil 0, terhalang tabel 0)

Tabel model: `absensi_harian`. File CI4: `app/Models/AttendanceModel.php`. File Laravel: `app/Models/AttendanceModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `apakahMilikIzin(?object $row, int $izinId, string $permitStatus): bool` | 0 | - | - | - | - |

## AttendanceSettingModel — 1 method (dipanggil 1, terhalang tabel 0)

Tabel model: `absensi_setting`. File CI4: `app/Models/AttendanceSettingModel.php`. File Laravel: `app/Models/AttendanceSettingModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `first()` | 44 (Co:Commands/CheckDendaCommand.php, Co:Commands/DapodikSyncCommand.php, C:Admin/JadwalController.php, C:Admin/JurnalController.php, C:Admin/KalenderController.php, C:Admin/LemburController.php, C:Admin/PoinGuruController.php, C:Admin/PresensiAdminController.php, C:Admin/RewardController.php, C:Api/TelegramBotController.php, C:Api/WhatsappWebhookController.php, C:AuthController.php, C:BaseController.php, C:CronControllerController.php, C:DashboardController.php, C:DataguruController.php, C:DatasiswaController.php, C:Guru/AbsensiController.php, C:Guru/AttendanceController.php, C:Guru/JurnalController.php, C:Guru/LemburController.php, C:Guru/PresensiSiswaQrController.php, C:Guru/RewardController.php, C:PresensiSiswaController.php, C:ProfilsekolahController.php, C:SettingsController.php, C:Siswa/PresensiController.php, L:DapodikClient.php, L:RememberLogin.php, L:SuratNomor.php, L:SyncService.php, L:Theme.php, S:AttendancePenaltyService.php, S:CutiKategoriService.php, S:DapodikSyncService.php, S:GuruTerawalService.php, S:IzinKuotaService.php, S:NotificationService.php, S:OpenWAService.php, S:RekapPdfService.php, S:SiswaDendaService.php, S:SiswaPoinService.php, S:SuratPanggilanService.php, S:SuratTeguranService.php) | absensi_setting | - | - | COLLISION |

## AuditLogModel — 1 method (dipanggil 1, terhalang tabel 1)

Tabel model: `audit_logs`. File CI4: `app/Models/AuditLogModel.php`. File Laravel: `app/Models/AuditLogModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `log($tableName, $recordId, $action, $targetUserId = null, $actorId = null, $oldValues = null, $newValues = null, $reason = null)` | 7 (C:Admin/LemburController.php, C:Admin/PresensiAdminController.php, C:Admin/RewardController.php, C:CronControllerController.php, C:Guru/AttendanceController.php, C:Guru/PiketController.php, C:ProfilsekolahController.php) | audit_logs, user_id | user_id | - | - |

## RewardTierModel — 1 method (dipanggil 1, terhalang tabel 0)

Tabel model: `reward_tiers`. File CI4: `app/Models/RewardTierModel.php`. File Laravel: `app/Models/RewardTierModel.php`.

| Method | Dipanggil | Tabel | Tabel blm ada | BaseController | Catatan |
|---|---|---|---|---|---|
| `getTierByPoin($poin)` | 2 (C:Admin/RewardController.php, C:Guru/RewardController.php) | reward_tiers | - | - | - |

## FaceRegistrationRequestModel — 0 method (dipanggil 0, terhalang tabel 0)

Tabel model: `face_registration_requests`. File CI4: `app/Models/FaceRegistrationRequestModel.php`. File Laravel: `app/Models/FaceRegistrationRequestModel.php`.

_Tidak ada method kustom._

## RewardModel — 0 method (dipanggil 0, terhalang tabel 0)

Tabel model: `rewards`. File CI4: `app/Models/RewardModel.php`. File Laravel: `app/Models/RewardModel.php`.

_Tidak ada method kustom._

## ShiftAnggotaModel — 0 method (dipanggil 0, terhalang tabel 0)

Tabel model: `absensi_shift_anggota`. File CI4: `app/Models/ShiftAnggotaModel.php`. File Laravel: `app/Models/ShiftAnggotaModel.php`.

_Tidak ada method kustom._

---

## Lampiran: model yang dikecualikan (arsip, TIDAK di-port)

### CbtModel — 93 method (dipanggil 7) — EXCLUDED

Tabel model: `-`.

| Method | Dipanggil | Tabel | Tabel blm ada | Catatan |
|---|---|---|---|---|
| `getToken()` | 2 (C:CbttokenController.php, S:CbtService.php) | cbt_token | cbt_token | ret:getRow |
| `saveToken(array $data)` | 1 (C:CbttokenController.php) | cbt_token | cbt_token | - |
| `getJadwal(int $idJadwal): ?object` | 0 | cbt_bank_soal, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_jadwal, master_mapel | ret:getRow |
| `getAllJadwal(int $tp, int $smt): array` | 0 | cbt_bank_soal, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_jadwal, master_mapel | ret:getResult |
| `getAllJadwalDates(int $tp, int $smt): array` | 0 | cbt_jadwal | cbt_jadwal | ret:getResult |
| `getDistinctRuangSesi(int $tp, int $smt, array $arrKelas): array` | 0 | cbt_ruang, cbt_sesi, cbt_sesi_siswa | cbt_ruang, cbt_sesi, cbt_sesi_siswa | ret:getResult |
| `getDataJadwalByTgl(string $tanggal): array` | 0 | cbt_bank_soal, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_jadwal, master_mapel | ret:getResult |
| `getAllJadwalByTanggal($tp, $smt, $jenis, $guru = null)` | 0 | cbt_bank_soal, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_jadwal, master_mapel | ret:getResult |
| `getActiveJadwal(int $tp, int $smt): array` | 0 | cbt_bank_soal, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_jadwal, master_mapel | ret:getResult |
| `getAllRuang(): array` | 1 (C:UserpanitiaController.php) | cbt_ruang | cbt_ruang | ret:getResult |
| `getRuangSesi(int $tp, int $smt): array` | 0 | cbt_ruang, cbt_sesi, cbt_sesi_siswa, master_kelas | cbt_ruang, cbt_sesi, cbt_sesi_siswa, master_kelas | ret:getResult |
| `getRuangById(int $id): ?object` | 0 | cbt_ruang | cbt_ruang | ret:getRow |
| `getDistinctRuang(int $tp, int $smt, array $arrKelas): array` | 0 | cbt_ruang, cbt_sesi, cbt_sesi_siswa | cbt_ruang, cbt_sesi, cbt_sesi_siswa | ret:getResult |
| `getAllSesi(): array` | 0 | cbt_sesi | cbt_sesi | ret:getResult |
| `getSesiById(int $id): ?object` | 0 | cbt_sesi | cbt_sesi | ret:getRow |
| `getAllKodeSesi(): array` | 0 | cbt_sesi | cbt_sesi | ret:getResult |
| `getDistinctSesi(int $tp, int $smt, array $idsKelas): array` | 0 | cbt_ruang, cbt_sesi, cbt_sesi_siswa, master_kelas | cbt_ruang, cbt_sesi, cbt_sesi_siswa, master_kelas | ret:getResult |
| `getRuangSesiSiswa(int $kls, int $tp, int $smt): array` | 0 | cbt_sesi_siswa, kelas_siswa, master_siswa | cbt_sesi_siswa, kelas_siswa, master_siswa | ret:getResult |
| `getAllJenisUjian(bool $activeOnly = false): array` | 0 | cbt_jenis | cbt_jenis | ret:getResult |
| `getActiveJenisId(): ?int` | 0 | cbt_jenis | cbt_jenis | ret:getRow |
| `getJenis(): array` | 0 | cbt_jenis | cbt_jenis | ret:getResult |
| `updateJenis(int $id, array $data): bool` | 0 | cbt_jenis | cbt_jenis | - |
| `getAllBankSoal(int $tp, int $smt): array` | 0 | cbt_bank_soal, master_mapel | cbt_bank_soal, master_mapel | ret:getResult |
| `getBankSoalById(int $idBank): ?object` | 0 | cbt_bank_soal, master_guru, master_jurusan, master_mapel | cbt_bank_soal, master_guru, master_jurusan, master_mapel | ret:getRow |
| `getDataBank(?int $guru = null, ?int $mapel = null, ?int $level = null): array` | 0 | cbt_bank_soal, cbt_jadwal, cbt_soal, master_guru, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_soal, master_guru, master_mapel | ret:getResult |
| `getAllSoalByBank(int $idBank, ?int $jenis = null): array` | 0 | cbt_soal | cbt_soal | ret:getResult |
| `getJumlahJenisSoal(int $idBank): array` | 0 | cbt_soal | cbt_soal | ret:getResult |
| `getAllPengawas(int $tp, int $smt, ?int $ruang = null, ?int $sesi = null): array` | 0 | cbt_pengawas | cbt_pengawas | ret:getResult |
| `getPengawasByJadwal($tp, $smt, $id_jadwal, $sesi = null, $ruang = null): array` | 0 | cbt_pengawas | cbt_pengawas | ret:getResult |
| `saveLog(int $idSiswa, int $idJadwal, int $type): bool` | 0 | log_ujian | log_ujian | - |
| `getLogsByJadwal(int $idJadwal): array` | 0 | cbt_log, master_siswa | cbt_log, master_siswa | ret:getResult |
| `getSoalByBank(int $idBank, string $acak = '0', int $seed = 0)` | 0 | cbt_soal | cbt_soal | ret:getResult |
| `getJawabanSiswa(int $idSiswa, int $idJadwal): array` | 0 | cbt_jawaban | cbt_jawaban | ret:getResult |
| `getSiswaByKelasArray(int $tp, int $smt, array $kelas_ids): array` | 0 | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getAllNomorPeserta(int $tp, int $smt, array $siswa_ids): array` | 0 | cbt_nomor_peserta | cbt_nomor_peserta | ret:getResult |
| `cekNomorPeserta(array $ids_nomor): array` | 0 | cbt_nomor_peserta | cbt_nomor_peserta | ret:getResult |
| `hapus_duplikat_nomor_peserta(): int` | 0 | - | - | RAW-SQL; ret:affectedRows |
| `getAllJurusan(): array` | 1 (C:DatajurusanController.php) | master_jurusan | master_jurusan | ret:getResult |
| `getKelasByLevel(int $level, array $arrKelas): array` | 0 | master_kelas | master_kelas | ret:getResult |
| `dummy(string $jenjang): array` | 0 | - | - | - |
| `saveBankSoal(array $data, ?int $id = null)` | 0 | cbt_bank_soal | cbt_bank_soal | ret:insertID |
| `getDistinctJenisJadwal($tp, $smt): array` | 0 | cbt_jadwal | cbt_jadwal | ret:getResult |
| `getAllJenisUjianByArrJenis(array $arrJenis): array` | 0 | cbt_jenis | cbt_jenis | ret:getResult |
| `getDataJadwalByMapel($tp, $smt, $mapel = null): array` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_mapel | ret:getResult |
| `getJadwalById($id_jadwal, $sesi = null): ?object` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jenis, cbt_sesi, master_guru, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_jenis, cbt_sesi, master_guru, master_mapel | ret:getRow |
| `getJadwalByTanggal($tgl_mulai = null): array` | 0 | cbt_bank_soal, cbt_jadwal | cbt_bank_soal, cbt_jadwal | ret:getResult |
| `getJadwalByLevel(int $tp, int $smt, int $level): array` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_mapel | ret:getResult |
| `getKelas($tp, $smt)` | 1 (C:UserguruController.php) | master_kelas | master_kelas | ret:getResult |
| `getRuangSiswaByKelas(int $id_tp, int $id_smt, $kelas, $sesi = null): array` | 0 | cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getSiswaByRuang(int $id_tp, int $id_smt, int $id_ruang, $sesi, $level = null): array` | 0 | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, login_attempts, master_kelas, master_siswa, users | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, login_attempts, master_kelas, master_siswa, users | ret:getResult |
| `getSiswaByKelas(int $id_tp, int $id_smt, $id_kelas): array` | 2 (C:Guru/JurnalController.php, C:PresensiSiswaController.php) | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getSiswaByRuangId(int $id_tp, int $id_smt, int $id_ruang): array` | 0 | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getAllPesertaByRuang(int $id_tp, int $id_smt): array` | 0 | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getAllPesertaByKelas(int $id_tp, int $id_smt): array` | 0 | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | buku_induk, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getSettingKopAbsensi(): ?object` | 0 | cbt_kop_absensi, setting | cbt_kop_absensi, setting | ret:getRow |
| `getSettingKopBeritaAcara(): ?object` | 0 | cbt_kop_berita, setting | cbt_kop_berita, setting | ret:getRow |
| `getSettingKartu(): ?object` | 0 | cbt_kop_kartu | cbt_kop_kartu | ret:getRow |
| `getBeritaAcara($id_jadwal, $id_ruang, $id_sesi): ?object` | 0 | cbt_berita_acara | cbt_berita_acara | ret:getRow |
| `getPaktaIntegritas($id_jadwal, $id_ruang, $id_sesi): ?object` | 0 | cbt_pakta_integritas | cbt_pakta_integritas | ret:getRow |
| `saveBeritaAcara(array $data)` | 0 | cbt_berita_acara | cbt_berita_acara | ret:getRow |
| `savePaktaIntegritas(array $data)` | 0 | cbt_pakta_integritas | cbt_pakta_integritas | ret:getRow |
| `getAllBeritaAcara(int $tp, int $smt): array` | 0 | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, cbt_ruang, cbt_sesi, master_mapel | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, cbt_ruang, cbt_sesi, master_mapel | ret:getResult |
| `getAllPaktaIntegritas(int $tp, int $smt): array` | 0 | cbt_bank_soal, cbt_jadwal, cbt_pakta_integritas, cbt_ruang, cbt_sesi, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_pakta_integritas, cbt_ruang, cbt_sesi, master_mapel | ret:getResult |
| `getKelasUjian($tp, $smt, $kelas)` | 0 | cbt_sesi_siswa | cbt_sesi_siswa | ret:getResult |
| `getAllBankSoalByMapel($tp, $smt, $mapel, $guru = null)` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_level, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_level, master_mapel | ret:getResult,getRow |
| `saveJadwalUjian($tp, $smt)` | 0 | cbt_jadwal | cbt_jadwal | ret:insertID |
| `getAllDataJadwal($tp, $smt, $guru = null, $mapel = null, $level = null)` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_mapel | cbt_bank_soal, cbt_jadwal, cbt_jenis, master_guru, master_mapel | ret:getResult |
| `replaceKopKartu(array $data)` | 0 | cbt_kop_kartu | cbt_kop_kartu | - |
| `replaceKopAbsensi(array $data)` | 0 | cbt_kop_absensi | cbt_kop_absensi | - |
| `replaceKopBerita(array $data)` | 0 | cbt_kop_berita | cbt_kop_berita | - |
| `getPengawas($id_pengawas)` | 0 | cbt_pengawas | cbt_pengawas | ret:getRow |
| `getJadwalByArrId($arrId)` | 0 | cbt_jadwal | cbt_jadwal | ret:getResult |
| `cekSoalAda($id_bank, $jenis)` | 0 | cbt_soal | cbt_soal | ret:getResult |
| `cekSoalBelumKomplit($jenis, $opsi_ganda, $id_bank = null)` | 0 | cbt_soal | cbt_soal | ret:getResult |
| `getNomorSoalTerbesar($id_bank, $jenis)` | 0 | cbt_soal | cbt_soal | ret:getRow |
| `getSoalByNomor($bank_id, $nomor, $jenis)` | 0 | cbt_soal | cbt_soal | ret:getRow |
| `countSiswaTerpakai($idBank)` | 0 | cbt_siswa | cbt_siswa | ret:countAllResults |
| `maybeUnserialize($data)` | 0 | - | - | - |
| `olahNilai($type_query, $id_tp, $id_smt, $query, $id_jadwal, $koreksi = false)` | 0 | cbt_bank_soal, cbt_jadwal, cbt_jawaban, cbt_jenis, cbt_nilai, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, cbt_siswa, cbt_soal, master_guru, master_kelas, master_mapel, master_siswa | cbt_bank_soal, cbt_jadwal, cbt_jawaban, cbt_jenis, cbt_nilai, cbt_nomor_peserta, cbt_ruang, cbt_sesi, cbt_sesi_siswa, cbt_siswa, cbt_soal, master_guru, master_kelas, master_mapel, master_siswa | ret:getResult,getRow |
| `getBeritaAcaraById($id)` | 0 | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, master_mapel | ret:getRow |
| `getPaktaIntegritasById($id)` | 0 | cbt_pakta_integritas | cbt_pakta_integritas | ret:getRow |
| `getAllBeritaAcaraDetailed()` | 0 | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, master_mapel | cbt_bank_soal, cbt_berita_acara, cbt_jadwal, master_mapel | ret:getResult |
| `saveBeritaAcaraSimple(array $data)` | 0 | cbt_berita_acara | cbt_berita_acara | - |
| `savePaktaSimple(array $data)` | 0 | cbt_pakta_integritas | cbt_pakta_integritas | - |
| `getJenisUjianById($id)` | 0 | cbt_jenis | cbt_jenis | ret:getRow |
| `hitungNilai($id_siswa, $id_jadwal)` | 1 (S:CbtService.php) | cbt_bank_soal, cbt_jadwal, cbt_jawaban, cbt_nilai, cbt_soal | cbt_bank_soal, cbt_jadwal, cbt_jawaban, cbt_nilai, cbt_soal | RAW-SQL; ret:getResult,getRow,countAllResults |
| `batchHitungNilai(array $studentIds, int $idJadwal): array` | 0 | - | - | - |
| `getScoringStats(int $idJadwal): array` | 0 | cbt_nilai | cbt_nilai | ret:getResult,getRow |
| `normalizeAnswer($answer): string` | 0 | - | - | - |
| `calculateMatchingScore($kunci, $jawab): float` | 0 | - | - | - |
| `parseArrayAnswer($answer): array` | 0 | - | - | - |
| `parseAcceptableAnswers($kunci): array` | 0 | - | - | - |
| `getStatusUjianLabel($status, $mulai, $selesai): string` | 0 | - | - | - |

### RaporModel — 70 method (dipanggil 1) — EXCLUDED

Tabel model: `-`.

| Method | Dipanggil | Tabel | Tabel blm ada | Catatan |
|---|---|---|---|---|
| `getKikdMapel($id, $id_tp, $id_smt)` | 0 | rapor_kikd | rapor_kikd | ret:getRow |
| `getKikdMapelKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_kikd | rapor_kikd | ret:getResult |
| `getKkm($id)` | 0 | rapor_kkm | rapor_kkm | ret:getRow |
| `getArrKkm($ids)` | 0 | rapor_kkm | rapor_kkm | ret:getResult |
| `getRaporSetting($id_tp, $id_smt)` | 0 | rapor_admin_setting | rapor_admin_setting | ret:getRow |
| `getDetailSiswa($id_kelas, $id_tp, $id_smt)` | 0 | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | RAW-SQL; ret:getResult |
| `getDetailSiswaById($id_siswa, $id_tp, $id_smt)` | 0 | kelas_siswa, master_kelas, master_siswa | kelas_siswa, master_kelas, master_siswa | ret:getRow |
| `cekNilaiHarianKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:countAllResults |
| `getNilaiHarianKelas($id_mapel, $id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:getRow |
| `getAllNilaiHarianKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:getResult |
| `cekNilaiPtsKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:countAllResults |
| `getIdNilaiPts($arr_id)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:getResult |
| `getNilaiPtsKelas($id_mapel, $id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:getRow |
| `getAllNilaiPtsKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:getResult |
| `getEkstraKelas($id_mapel, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_ekstra | rapor_nilai_ekstra | ret:getRow |
| `cekNilaiEkstraKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_ekstra | rapor_nilai_ekstra | ret:countAllResults |
| `getNilaiEkstraKelas($id_ekstra, $id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_ekstra | rapor_nilai_ekstra | ret:getRow |
| `getAllEkstra()` | 0 | kelas_ekstra | kelas_ekstra | ret:getResult |
| `getAllNilaiEkstraKelas($id_ekstra, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_ekstra | rapor_nilai_ekstra | ret:getResult |
| `cekNilaiAkhirKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir | rapor_nilai_akhir | ret:countAllResults |
| `getNilaiAkhirKelas($id_mapel, $id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | ret:getRow |
| `getAllNilaiAkhirKelas($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | ret:getResult |
| `getNilaiAkhirByMapel($id_mapel, $id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts | ret:getResult |
| `getDeskripsiSikap($kelas, $id_tp, $id_smt)` | 0 | rapor_data_sikap | rapor_data_sikap | ret:getResult |
| `getAllDeskripsiSikap($kelas)` | 0 | rapor_data_sikap | rapor_data_sikap | ret:getResult |
| `getDeskripsiSikapByJenis($kelas, $jenis, $id_tp, $id_smt)` | 0 | rapor_data_sikap | rapor_data_sikap | ret:getResult |
| `getNilaiSikapKelas($id_kelas, $id_siswa, $id_tp, $id_smt, $jenis)` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getRow |
| `getAllNilaiSikapKelas($id_kelas)` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getResult |
| `getNilaiSikapByJenis($id_kelas, $jenis, $id_tp, $id_smt)` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getResult |
| `getNilaiSikapByKelas($id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getResult |
| `getNilaiSikapBySiswa($id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getResult |
| `getDeskripsiCatatanByJenis($kelas, $jenis, $id_tp, $id_smt)` | 0 | rapor_data_catatan | rapor_data_catatan | ret:getResult |
| `getCatatanKelas($id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_catatan_wali | rapor_catatan_wali | ret:getRow |
| `getAllCatatanKelas($id_kelas)` | 0 | rapor_catatan_wali | rapor_catatan_wali | ret:getResult |
| `getRankingKelas($id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_prestasi | rapor_prestasi | ret:getRow |
| `getAllRankingKelas($id_kelas)` | 0 | rapor_prestasi | rapor_prestasi | ret:getResult |
| `getAllDeskripsiFisikKelas()` | 0 | rapor_data_fisik | rapor_data_fisik | ret:getResult |
| `getAllRaporFisik()` | 0 | rapor_fisik | rapor_fisik | ret:getResult |
| `getDeskripsiFisikKelas($kelas, $id_tp, $id_smt)` | 0 | rapor_data_fisik | rapor_data_fisik | ret:getRow |
| `getFisikKelas($id_kelas, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_fisik | rapor_fisik | ret:getRow |
| `getAllFisikKelas($id_kelas)` | 0 | rapor_fisik | rapor_fisik | ret:getResult |
| `getJmlNilaiMapelHarianSiswa($id_mapel, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:getRow |
| `getNilaiMapelHarianSiswa($id_mapel, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:getRow |
| `getArrNilaiMapelHarianSiswa($ids_mapel, $ids_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_harian | rapor_nilai_harian | ret:getResult |
| `getNilaiMapelPtsSiswa($id_mapel, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:getRow |
| `getArrNilaiMapelPtsSiswa($ids_mapel, $ids_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_pts | rapor_nilai_pts | ret:getResult |
| `getNilaiMapelPasSiswa($id_mapel, $id_siswa, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir | rapor_nilai_akhir | ret:getRow |
| `getAllNilaiRapor($ids_siswa = null)` | 0 | master_guru, master_jurusan, master_kelas, master_siswa, master_smt, master_tp, rapor_catatan_wali, rapor_fisik, rapor_naik, rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts, rapor_prestasi | master_guru, master_jurusan, master_kelas, master_siswa, master_smt, master_tp, rapor_catatan_wali, rapor_fisik, rapor_naik, rapor_nilai_akhir, rapor_nilai_harian, rapor_nilai_pts, rapor_prestasi | RAW-SQL; ret:getResult |
| `getKenaikanSiswa($id_kelas, $id_tp, $id_smt, $level = null)` | 0 | kelas_siswa, master_kelas, master_siswa, rapor_naik | kelas_siswa, master_kelas, master_siswa, rapor_naik | ret:getResult |
| `getSiswaLulus($id_tp, $id_smt, $level)` | 0 | kelas_siswa, master_kelas, master_siswa, rapor_naik | kelas_siswa, master_kelas, master_siswa, rapor_naik | ret:getResult |
| `getJumlahLulus($id_tp, $id_smt, $level)` | 0 | kelas_siswa, master_kelas, rapor_naik | kelas_siswa, master_kelas, rapor_naik | ret:countAllResults |
| `getKenaikanRapor($id_kelas, $id_tp, $id_smt)` | 0 | kelas_siswa, rapor_naik | kelas_siswa, rapor_naik | ret:getResult |
| `getAllRaporSetting()` | 0 | rapor_admin_setting | rapor_admin_setting | ret:getResult |
| `getAllKkm()` | 0 | rapor_kkm | rapor_kkm | ret:getResult |
| `getAllKkmRaporAkhir($kelas, $id_tp, $id_smt)` | 0 | rapor_kkm | rapor_kkm | ret:getResult |
| `getAllNilaiEkstra()` | 0 | rapor_nilai_ekstra | rapor_nilai_ekstra | ret:getResult |
| `getAllNilaiSikap()` | 0 | rapor_nilai_sikap | rapor_nilai_sikap | ret:getResult |
| `getAllFisik()` | 0 | rapor_fisik | rapor_fisik | ret:getResult |
| `getDataKumpulanRapor($kelas = null, $tp = null, $smt = null)` | 0 | buku_nilai, master_siswa | buku_nilai, master_siswa | ret:getResult |
| `exists($uid, $tp, $smt, $kelas)` | 1 (S:GuruTerawalService.php) | buku_nilai | buku_nilai | COLLISION; ret:countAllResults |
| `deleteNilaiRapor()` | 0 | rapor_catatan_wali, rapor_fisik, rapor_naik, rapor_nilai_akhir, rapor_nilai_ekstra, rapor_nilai_harian, rapor_nilai_pts, rapor_nilai_sikap, rapor_prestasi | rapor_catatan_wali, rapor_fisik, rapor_naik, rapor_nilai_akhir, rapor_nilai_ekstra, rapor_nilai_harian, rapor_nilai_pts, rapor_nilai_sikap, rapor_prestasi | - |
| `getNilaiRaporByKelas($id_kelas, $id_tp, $id_smt)` | 0 | rapor_nilai_akhir, rapor_nilai_harian | rapor_nilai_akhir, rapor_nilai_harian | ret:getResult |
| `getPrestasiByKelas($id_kelas, $id_tp, $id_smt)` | 0 | rapor_prestasi | rapor_prestasi | ret:getResult |
| `getCatatanWaliByKelas($id_kelas, $id_tp, $id_smt)` | 0 | rapor_catatan_wali | rapor_catatan_wali | ret:getResult |
| `getDummyDeskripsiSpiritual()` | 0 | - | - | - |
| `getDummyDeskripsiSosial()` | 0 | - | - | - |
| `getDummyDeskripsiAbsensi()` | 0 | - | - | - |
| `getDummyDeskripsiCatatan()` | 0 | - | - | - |
| `getDummyDeskripsiRanking()` | 0 | - | - | - |
| `getDummyDeskripsiFisik($jenis)` | 0 | - | - | - |

### CbtSiswaModel — 10 method (dipanggil 1) — EXCLUDED

Tabel model: `cbt_siswa`.

| Method | Dipanggil | Tabel | Tabel blm ada | Catatan |
|---|---|---|---|---|
| `getCbtSiswa(int $idSiswa, int $idJadwal): ?object` | 1 (S:CbtService.php) | cbt_siswa | cbt_siswa | - |
| `getAllByJadwal(int $idJadwal): array` | 0 | cbt_siswa, kelas_siswa, master_kelas, master_siswa | cbt_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getSiswaSedangUjian(int $idJadwal): array` | 0 | cbt_siswa, kelas_siswa, master_kelas, master_siswa | cbt_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `getSiswaSelesaiUjian(int $idJadwal): array` | 0 | cbt_siswa, kelas_siswa, master_kelas, master_siswa | cbt_siswa, kelas_siswa, master_kelas, master_siswa | ret:getResult |
| `startExam(int $idSiswa, int $idJadwal, int $idBank): int` | 0 | cbt_siswa | cbt_siswa | - |
| `endExam(int $idCbtSiswa): bool` | 0 | cbt_siswa | cbt_siswa | - |
| `resetSelesai(int $idCbtSiswa): bool` | 0 | cbt_siswa | cbt_siswa | - |
| `resetLogin(int $idCbtSiswa): bool` | 0 | cbt_siswa | cbt_siswa | - |
| `resetWaktu(int $idCbtSiswa, int $additionalMinutes = 15): bool` | 0 | cbt_siswa | cbt_siswa | - |
| `getStatistikJadwal(int $idJadwal): object` | 0 | cbt_siswa | cbt_siswa | ret:countAllResults |

### CbtReportModel — 6 method (dipanggil 0) — EXCLUDED

Tabel model: `-`.

| Method | Dipanggil | Tabel | Tabel blm ada | Catatan |
|---|---|---|---|---|
| `getJadwalSummary(int $idJadwal): array` | 0 | - | - | RAW-SQL; ret:getRowArray,getRow |
| `getScoresPaginated(int $idJadwal, int $page = 1, int $perPage = 50): array` | 0 | cbt_nilai, cbt_siswa, master_kelas, master_siswa | cbt_nilai, cbt_siswa, master_kelas, master_siswa | RAW-SQL; ret:getResultArray,getResult |
| `getQuestionAnalysis(int $idJadwal, int $idBank): array` | 0 | cbt_jawaban, cbt_soal | cbt_jawaban, cbt_soal | RAW-SQL; ret:getResultArray,getResult |
| `streamScoresToCsv(int $idJadwal, string $filename = 'rekap_nilai.csv'): void` | 0 | - | - | - |
| `getScoreDistribution(int $idJadwal): array` | 0 | - | - | RAW-SQL; ret:getResultArray,getResult |
| `batchCalculateScores(int $idJadwal, int $batchSize = 50): int` | 0 | cbt_nilai, cbt_siswa | cbt_nilai, cbt_siswa | RAW-SQL; ret:getResultArray,getResult |

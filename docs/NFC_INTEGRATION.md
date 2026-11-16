# Integrasi NFC untuk Absensi

## Ringkasan

Sistem ini memungkinkan guru dan siswa melakukan absensi dengan menempelkan kartu NFC/RFID ke perangkat Android yang berfungsi sebagai reader.

## Arsitektur

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│  Kartu NFC  │ ──Tap──▶│   Android   │ ──HTTP──▶│   Server    │
│  (RFID)     │         │   (Reader)  │   POST   │   Laravel   │
└─────────────┘         └─────────────┘         └─────────────┘
                              │                       │
                              │    API Request        │
                              │    (nfc_uid, lat,     │
                              │     lng, device_id)   │
                              │                       ▼
                              │              ┌─────────────────┐
                              │              │ NfcController   │
                              │              │ ↓               │
                              │              │ NfcAttendance   │
                              │              │ Service         │
                              │              │ ↓               │
                              │              │ Database        │
                              │              │ - absensi_harian│
                              │              │ - presensi_siswa│
                              │              │ - nfc_scan_log  │
                              └──────────────┴─────────────────┘
```

## API Endpoints

### 1. Scan NFC (Absensi)

**Endpoint:** `POST /api/nfc/scan`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "nfc_uid": "04A2B3C4D5E6",
    "jenis_scan": "masuk",
    "lat": -6.2088,
    "lng": 106.8456,
    "accuracy": 10,
    "device_id": "android-abc123",
    "api_key": "your-api-key-here"
}
```

**Parameter:**
| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| nfc_uid | string | Ya | UID kartu NFC yang di-scan |
| jenis_scan | string | Ya | "masuk" atau "pulang" |
| lat | float | Tidak | Latitude GPS perangkat |
| lng | float | Tidak | Longitude GPS perangkat |
| accuracy | int | Tidak | Akurasi GPS dalam meter |
| device_id | string | Tidak | ID unik perangkat Android |
| api_key | string | Ya | API key untuk autentikasi |

**Response Sukses:**
```json
{
    "success": true,
    "message": "Absen masuk berhasil (Tepat waktu)",
    "status": "SUCCESS",
    "nama": "John Doe",
    "tipe": "guru",
    "jam": "07:25:00",
    "telat_menit": 0,
    "denda": 0
}
```

**Response Error:**
```json
{
    "success": false,
    "message": "Kartu NFC tidak terdaftar dalam sistem",
    "status": "NOT_REGISTERED"
}
```

**Status Codes:**
| Status | Deskripsi |
|--------|-----------|
| SUCCESS | Absensi berhasil dicatat |
| NOT_REGISTERED | Kartu NFC tidak terdaftar |
| ALREADY_CHECKED | Sudah melakukan absensi dengan jenis yang sama hari ini |
| TIME_INVALID | Diluar waktu absensi yang diizinkan |
| HOLIDAY | Hari libur |
| NO_CHECKIN | Belum ada absen masuk (untuk check-out) |
| RATE_LIMITED | Terlalu banyak permintaan |
| UNAUTHORIZED | API key tidak valid |
| SYSTEM_ERROR | Error sistem |

### 2. Cek Kartu

**Endpoint:** `POST /api/nfc/check`

**Body:**
```json
{
    "nfc_uid": "04A2B3C4D5E6",
    "api_key": "your-api-key-here"
}
```

**Response:**
```json
{
    "success": true,
    "registered": true,
    "nama": "John Doe",
    "tipe": "guru"
}
```

### 3. Daftar Kartu (Sinkronisasi)

**Endpoint:** `POST /api/nfc/cards`

**Body:**
```json
{
    "api_key": "your-api-key-here"
}
```

**Response:**
```json
{
    "success": true,
    "count": 150,
    "cards": [
        {
            "nfc_uid": "04A2B3C4D5E6",
            "nama": "John Doe",
            "tipe": "guru"
        },
        {
            "nfc_uid": "04B3C4D5E6F7",
            "nama": "Jane Smith",
            "tipe": "siswa"
        }
    ]
}
```

## Konfigurasi API Key

API key dapat dikonfigurasi di salah satu lokasi berikut (diprioritaskan):

1. Tabel `setting` - kolom `api_key`
2. Tabel `absensi_setting` - kolom `api_key`
3. File `.env` - variabel `NFC_API_KEY`

```env
# .env
NFC_API_KEY=your-secret-api-key-here
```

## Database Schema

### Tabel `nfc_scan_log`

Mencatat setiap tap kartu NFC untuk audit trail.

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| nfc_uid | string(128) | UID kartu NFC |
| tipe_user | enum | 'guru' atau 'siswa' |
| user_id | int | ID dari tabel users |
| reference_id | int | id_guru atau id_siswa |
| nama | string(150) | Nama lengkap |
| jenis_scan | enum | 'masuk' atau 'pulang' |
| tanggal | date | Tanggal scan |
| jam | time | Jam scan |
| lat, lng | decimal | Koordinat GPS |
| device_id | string(100) | ID perangkat Android |
| berhasil | boolean | Status keberhasilan |
| pesan | string(255) | Pesan hasil |
| presensi_id | string | ID record absensi |

### Field `nomor_rfid`

Field ini sudah ada di tabel:
- `master_guru.nomor_rfid` - Untuk kartu guru
- `master_siswa.nomor_rfid` - Untuk kartu siswa

## Penggunaan di Android

### Contoh Kode Kotlin

```kotlin
// AndroidManifest.xml - Tambahkan permission
<uses-permission android:name="android.permission.NFC" />
<uses-feature android:name="android.hardware.nfc" android:required="true" />

// NfcManager.kt
class NfcManager(private val context: Context) {
    private val nfcAdapter: NfcAdapter? = NfcAdapter.getDefaultAdapter(context)
    
    fun enableReaderMode(activity: Activity, callback: (String) -> Unit) {
        nfcAdapter?.enableReaderMode(activity, { tag ->
            val uid = tag.id?.let { bytesToHex(it) } ?: return@enableReaderMode
            callback(uid)
        }, NfcAdapter.FLAG_READER_NFC_A or NfcAdapter.FLAG_READER_NFC_B, null)
    }
    
    private fun bytesToHex(bytes: ByteArray): String {
        return bytes.joinToString("") { "%02X".format(it) }
    }
}

// ApiService.kt
suspend fun scanNfc(
    nfcUid: String,
    jenisScan: String,
    lat: Double?,
    lng: Double?,
    deviceId: String,
    apiKey: String
): ScanResponse {
    return apiService.scanNfc(mapOf(
        "nfc_uid" to nfcUid,
        "jenis_scan" to jenisScan,
        "lat" to lat,
        "lng" to lng,
        "device_id" to deviceId,
        "api_key" to apiKey
    ))
}
```

### Flow Aplikasi Android

1. **Inisialisasi**
   - Cek ketersediaan NFC hardware
   - Minta permission NFC
   - Load daftar kartu dari server untuk cache lokal (opsional)

2. **Saat Kartu Di-tap**
   - Baca UID kartu
   - Ambil lokasi GPS (opsional tapi direkomendasikan)
   - Tentukan jenis scan (masuk/pulang) - bisa otomatis berdasarkan waktu
   - Kirim request ke server

3. **Tampilkan Hasil**
   - Sukses: Tampilkan nama, jam, dan status (tepat waktu/telat)
   - Gagal: Tampilkan pesan error

4. **Offline Mode (Opsional)**
   - Cache daftar kartu untuk validasi offline
   - Queue scan saat offline
   - Sync saat online

## Keamanan

1. **API Key Validation** - Setiap request harus menyertakan API key yang valid
2. **Rate Limiting** - Maksimal 10 request per menit per device
3. **Audit Log** - Semua scan dicatat di `nfc_scan_log`
4. **GPS Validation** - Opsional, untuk memastikan scan dari lokasi yang valid

## Troubleshooting

### Kartu Tidak Terbaca
- Pastikan `nomor_rfid` sudah diisi di tabel `master_guru` atau `master_siswa`
- Format UID harus sesuai (case-insensitive)

### API Key Tidak Valid
- Cek API key di tabel `setting`, `absensi_setting`, atau `.env`
- Pastikan API key tidak kosong atau placeholder

### Duplikat Scan
- Sistem memblokir scan duplikat untuk jenis yang sama pada hari yang sama
- Cek tabel `nfc_scan_log` untuk history

## File yang Dibuat

1. `database/migrations/2026_11_16_000003_create_nfc_scan_log_table.php` - Migration untuk log
2. `app/Models/NfcScanLogModel.php` - Model untuk log
3. `app/Services/NfcAttendanceService.php` - Business logic untuk proses absensi
4. `app/Http/Controllers/Api/NfcController.php` - API controller
5. `routes/web.php` - Route definitions (ditambahkan 3 endpoint)

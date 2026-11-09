<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi Pusat <-> Lokal
    |--------------------------------------------------------------------------
    |
    | Port dari Config\Sync (CI4). Nilai diambil dari environment dengan
    | default yang sama seperti CI4. Prioritas tetap: tabel `sync_settings`
    | dulu, lalu config ini (lihat App\Libraries\SyncService::getSetting).
    | API key default kosong — JANGAN menulis kunci asli di file ini.
    |
    */

    'server_mode' => env('SYNC_SERVER_MODE', 'lokal'),

    'pusat_url' => env('SYNC_PUSAT_URL', ''),

    'api_key' => env('SYNC_API_KEY', ''),

    'timeout' => (int) env('SYNC_TIMEOUT', 60),

    'retry_attempts' => (int) env('SYNC_RETRY_ATTEMPTS', 3),

    'batch_size' => (int) env('SYNC_BATCH_SIZE', 100),

    'enable_logging' => (bool) env('SYNC_ENABLE_LOGGING', true),

    'enforce_signature' => (bool) env('SYNC_ENFORCE_SIGNATURE', true),

    'signature_drift' => (int) env('SYNC_SIGNATURE_DRIFT', 300),

    'enable_checksum' => (bool) env('SYNC_ENABLE_CHECKSUM', true),

    'sync_tables' => [
        // Data Master (Pusat → Lokal) - Sebelum Ujian
        'push_to_local' => [
            'master_siswa',
            'master_kelas',
            'master_mapel',
            'master_guru',
            'cbt_jenis',
            'cbt_bank_soal',
            'cbt_soal',
            'cbt_jadwal',
            'cbt_sesi',
            'cbt_ruang',
            'cbt_sesi_siswa',
            'kelas_siswa',
        ],

        // Hasil Ujian (Lokal → Pusat) - Setelah Ujian
        'push_to_pusat' => [
            'cbt_siswa',
            'cbt_jawaban',
            'cbt_nilai',
            'cbt_rekap_siswa',
            'cbt_rekap_jadwal',
        ],
    ],
];

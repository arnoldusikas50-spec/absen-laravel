<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dapodik Web Service
    |--------------------------------------------------------------------------
    |
    | Pengaturan koneksi ke Web Service Dapodik lokal sekolah. Nilai diambil
    | dari environment; default kosong agar tidak ada kredensial/URL yang
    | terkunci di kode. Bila tabel `dapodik_settings` terisi, nilainya
    | mengalahkan config ini (lihat App\Libraries\DapodikClient).
    |
    */

    'server_url' => env('DAPODIK_SERVER_URL', 'http://localhost:5774'),

    'npsn' => env('DAPODIK_NPSN', ''),

    'secret_token' => env('DAPODIK_SECRET_TOKEN', ''),

    'timeout' => (int) env('DAPODIK_TIMEOUT', 15),
];

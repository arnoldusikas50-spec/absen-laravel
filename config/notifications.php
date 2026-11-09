<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notifikasi (Telegram / WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Fallback environment untuk kunci notifikasi. Sumber utama tetap baris
    | `absensi_setting` / `setting` di database (lihat
    | App\Services\NotificationService::NOTIFICATION_KEYS) — config ini
    | hanya dipakai bila nilai di database kosong. Semua default kosong;
    | JANGAN menulis token/kredensial asli di file ini.
    |
    */

    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    'telegram_chat_id' => env('TELEGRAM_CHAT_ID', ''),

    'wa_gateway_url' => env('WA_GATEWAY_URL', ''),

    'wa_token' => env('WA_TOKEN', ''),

    'wa_target_number' => env('WA_TARGET_NUMBER', ''),

    'wa_gateway_mode' => env('WA_GATEWAY_MODE', ''),

    'wa_base_url' => env('WA_BASE_URL', ''),

    'wa_api_key' => env('WA_API_KEY', ''),

    'wa_session_name' => env('WA_SESSION_NAME', ''),
];

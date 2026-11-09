<?php

/**
 * Helper utilities for generating URL-safe encrypted tokens.
 *
 * Port dari CodeIgniter 4 ke Laravel 13. CI4 `service('encrypter')`
 * diganti `Illuminate\Support\Facades\Crypt` (AES-256-CBC/GCM via
 * APP_KEY). Format token TIDAK kompatibel dengan token yang dibuat
 * aplikasi CI4 — token ini hanya dipakai sesaat di URL, jadi token
 * lama yang belum dipakai tidak bisa didekripsi setelah migrasi.
 */

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode(string $data)
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}

if (!function_exists('encrypt_id_token')) {
    /**
     * Encrypt numeric/string identifier for safe URL usage.
     */
    function encrypt_id_token($payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            $ciphertext = Crypt::encryptString((string) $payload);
            return base64url_encode($ciphertext);
        } catch (\Throwable $e) {
            Log::error('encrypt_id_token failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('decrypt_id_token')) {
    /**
     * Decode encrypted identifier back to integer.
     */
    function decrypt_id_token(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        try {
            $binary = base64url_decode($token);
            if ($binary === false) {
                return null;
            }

            $plain = Crypt::decryptString($binary);

            if ($plain === false || !ctype_digit((string) $plain)) {
                return null;
            }

            return (int) $plain;
        } catch (\Throwable $e) {
            Log::warning('decrypt_id_token failed: ' . $e->getMessage());
            return null;
        }
    }
}

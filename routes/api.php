<?php

use App\Http\Controllers\Api\NfcController;
use App\Http\Controllers\Api\RekapHarianController;
use App\Http\Controllers\Api\TelegramBotController;
use App\Http\Controllers\Api\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

// Port dari grup $routes->group('api') di CI4 Routes.php.
// Auth via API key di controller (tanpa CSRF / tanpa session).

Route::get('rekap-harian', [RekapHarianController::class, 'harian'])->name('api.rekap-harian.harian');
Route::get('telegram/setup', [TelegramBotController::class, 'setup'])->name('api.telegram.setup');
Route::post('telegram/webhook', [TelegramBotController::class, 'webhook'])->name('api.telegram.webhook');
Route::get('whatsapp/setup', [WhatsappWebhookController::class, 'setup'])->name('api.whatsapp.setup');
Route::post('whatsapp/webhook', [WhatsappWebhookController::class, 'webhook'])->name('api.whatsapp.webhook');

// NFC API Routes (untuk aplikasi Android). Autentikasi via API key, bukan
// session login. Berada di grup 'api' agar POST dari perangkat tidak terkena
// CSRF 419 seperti ketika masih terdaftar di routes/web.php.
Route::post('nfc/scan', [NfcController::class, 'scan'])->name('api.nfc.scan');
Route::post('nfc/check', [NfcController::class, 'checkCard'])->name('api.nfc.check');
Route::post('nfc/cards', [NfcController::class, 'listCards'])->name('api.nfc.cards');

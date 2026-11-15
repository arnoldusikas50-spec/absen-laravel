<?php

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

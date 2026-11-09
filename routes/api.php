<?php

use Illuminate\Support\Facades\Route;

// Port dari grup $routes->group('api') di CI4 Routes.php.
// Auth via API key di controller (tanpa CSRF / tanpa session).

Route::get('rekap-harian', [App\Http\Controllers\Api\RekapHarianController::class, 'harian'])->name('api.rekap-harian.harian');
Route::get('telegram/setup', [App\Http\Controllers\Api\TelegramBotController::class, 'setup'])->name('api.telegram.setup');
Route::post('telegram/webhook', [App\Http\Controllers\Api\TelegramBotController::class, 'webhook'])->name('api.telegram.webhook');
Route::get('whatsapp/setup', [App\Http\Controllers\Api\WhatsappWebhookController::class, 'setup'])->name('api.whatsapp.setup');
Route::post('whatsapp/webhook', [App\Http\Controllers\Api\WhatsappWebhookController::class, 'webhook'])->name('api.whatsapp.webhook');

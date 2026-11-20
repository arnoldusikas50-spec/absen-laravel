<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20261004120000_CreateNotifChannelConfig.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notif_channel_config', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('jenis', 40)->comment('Identifikasi unik jenis notifikasi');
            $table->boolean('tg_grup')->default(1)->comment('1 = kirim ke Telegram grup sekolah');
            $table->boolean('wa_grup')->default(1)->comment('1 = kirim ke WhatsApp grup/nomor broadcast');
            $table->boolean('tg_personal')->default(1)->comment('1 = kirim ke Telegram personal guru');
            $table->boolean('wa_personal')->default(1)->comment('1 = kirim ke WhatsApp personal guru');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notif_channel_config');
    }
};

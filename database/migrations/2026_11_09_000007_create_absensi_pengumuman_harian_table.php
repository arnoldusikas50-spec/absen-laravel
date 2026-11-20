<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260930120000_CreateAbsensiPengumumanHarianTable.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_pengumuman_harian', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->date('tgl');
            $table->unsignedInteger('user_id');
            $table->string('nama_guru', 191);
            $table->dateTime('jam_masuk')->nullable();
            $table->boolean('dm_terkirim')->default(0)->comment('1 bila DM personal ke guru tersebut berhasil');
            $table->boolean('grup_terkirim')->default(0)->comment('1 bila pengumuman grup berhasil');
            $table->string('catatan', 255)->nullable()->comment('Alasan DM gagal, mis. guru belum menautkan Telegram');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('tgl');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_pengumuman_harian');
    }
};

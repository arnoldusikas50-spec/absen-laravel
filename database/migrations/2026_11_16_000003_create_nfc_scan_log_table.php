<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel untuk mencatat log scan NFC dari perangkat Android.
 * Setiap tap kartu NFC/RFID akan tercatat di sini untuk audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfc_scan_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->id();
            $table->string('nfc_uid', 128)->index()->comment('UID kartu NFC/RFID yang di-scan');
            $table->enum('tipe_user', ['guru', 'siswa'])->index()->comment('Jenis pemilik kartu');
            $table->unsignedInteger('user_id')->nullable()->comment('ID dari tabel users');
            $table->unsignedInteger('reference_id')->nullable()->comment('id_guru atau id_siswa');
            $table->string('nama', 150)->nullable()->comment('Nama lengkap untuk cepat lookup');
            $table->enum('jenis_scan', ['masuk', 'pulang'])->default('masuk')->comment('Jenis absensi');
            $table->date('tanggal')->index();
            $table->time('jam')->nullable();
            $table->decimal('lat', 10, 8)->nullable()->comment('GPS latitude dari perangkat');
            $table->decimal('lng', 11, 8)->nullable()->comment('GPS longitude dari perangkat');
            $table->integer('akurasi_gps')->nullable()->comment('Akurasi GPS dalam meter');
            $table->string('device_id', 100)->nullable()->index()->comment('ID unik perangkat Android');
            $table->string('ip_address', 45)->nullable();
            $table->boolean('berhasil')->default(true)->comment('Apakah scan berhasil mencatat absensi');
            $table->string('pesan', 255)->nullable()->comment('Pesan hasil scan');
            $table->string('presensi_id', 50)->nullable()->comment('ID record absensi yang dibuat');
            $table->timestamps();

            $table->index(['nfc_uid', 'tanggal', 'jenis_scan']);
            $table->index(['tipe_user', 'reference_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfc_scan_log');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_pembayaran_denda`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - RUNTIME:PresensiAdmin.php#pembayaran_denda
 *  - 20260615000001_AddNominalToRewards.php
 *  - 20261009000001_AddUniquePembayaranVoucher.php
 *  - 20261009000002_AddUniquePembayaranIdempotency.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_pembayaran_denda', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->integer('bulan');
            $table->integer('tahun');
            $table->decimal('total_denda', 15, 2)->default(0);
            $table->decimal('nominal_bayar', 15, 2)->default(0);
            $table->string('metode_bayar', 20)->default('TUNAI');
            $table->integer('poin_digunakan')->default(0);
            $table->unsignedInteger('reward_redemption_id')->nullable();
            $table->string('status', 20)->nullable()->default('lunas');
            $table->text('keterangan')->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedInteger('paid_by')->nullable();
            $table->string('idempotency_key', 80)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('user_id');
            $table->unique('reward_redemption_id', 'uniq_pembayaran_voucher_redemption');
            $table->unique('idempotency_key', 'uniq_pembayaran_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_pembayaran_denda');
    }
};

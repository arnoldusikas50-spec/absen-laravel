<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_saldo_denda`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - RUNTIME:PresensiAdmin.php#pembayaran_denda
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_saldo_denda', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('tipe', 30);
            $table->decimal('nominal', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->unsignedInteger('pembayaran_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_saldo_denda');
    }
};

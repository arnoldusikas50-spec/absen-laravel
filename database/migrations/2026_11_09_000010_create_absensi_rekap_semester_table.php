<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_rekap_semester`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260617000003_AddAttendanceAlertColumns.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_rekap_semester', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->integer('id_tp')->nullable()->comment('Tahun Pelajaran');
            $table->integer('id_smt')->nullable()->comment('Semester');
            $table->smallInteger('tahun');
            $table->tinyInteger('semester')->comment('1 = Ganjil, 2 = Genap');
            $table->smallInteger('total_hari_efektif')->default(0);
            $table->smallInteger('total_hadir')->default(0);
            $table->smallInteger('total_telat')->default(0);
            $table->smallInteger('total_izin')->default(0);
            $table->smallInteger('total_sakit')->default(0);
            $table->smallInteger('total_alpha')->default(0);
            $table->smallInteger('total_dinas')->default(0);
            $table->decimal('persentase_kehadiran', 5, 2)->default(0.0);
            $table->integer('total_poin')->default(0);
            $table->decimal('total_denda', 12, 2)->default(0.0);
            $table->enum('status_kelayakan', ['layak', 'perhatian', 'tidak_layak'])->default('layak')->comment('Kelayakan berdasarkan persentase kehadiran');
            $table->text('catatan')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['user_id', 'tahun', 'semester'], 'unik_rekap_semester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_rekap_semester');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `tracer_study_responses`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-090000_FixAlumniTables.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracer_study_responses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_alumni')->nullable();
            $table->string('nama', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->string('status_kegiatan', 50)->nullable();
            $table->string('nama_institusi', 255)->nullable();
            $table->string('bidang_pekerjaan', 100)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('gaji_range', 50)->nullable();
            $table->integer('waktu_tunggu_kerja')->nullable()->comment('Months');
            $table->string('kesesuaian_bidang', 50)->nullable();
            $table->string('pendidikan_lanjut', 50)->nullable();
            $table->string('nama_kampus', 200)->nullable();
            $table->string('program_studi', 150)->nullable();
            $table->tinyInteger('kepuasan_sekolah')->nullable()->comment('1-5 rating');
            $table->text('saran')->nullable();
            $table->json('jawaban_detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_alumni');
            $table->index('email');
            $table->index('tahun_lulus');
            $table->index('status_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_study_responses');
    }
};

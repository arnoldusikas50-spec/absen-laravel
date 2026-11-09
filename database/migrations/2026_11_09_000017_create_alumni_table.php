<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `alumni`.
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
        Schema::create('alumni', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama', 150);
            $table->string('nisn', 20)->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->string('email', 150);
            $table->string('no_hp', 20)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('jurusan', 100)->nullable();
            $table->string('foto', 255)->nullable();
            $table->boolean('status')->default(0)->comment('0: Pending, 1: Verified');
            $table->string('token', 64)->nullable();
            $table->boolean('tracer_filled')->default(0);
            $table->dateTime('tracer_filled_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('email');
            $table->unique('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};

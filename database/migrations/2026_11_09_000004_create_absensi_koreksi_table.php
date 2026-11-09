<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `absensi_koreksi`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 20260110040000_CreateCorrectionTable.php
 *  - 20260617000002_AddAttendancePerformanceIndexes.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_koreksi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('attendance_id');
            $table->unsignedInteger('user_id');
            $table->string('field_name', 50)->comment('jam_masuk, jam_pulang, status_masuk, etc');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('processed_by')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('attendance_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['status', 'created_at'], 'idx_koreksi_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_koreksi');
    }
};

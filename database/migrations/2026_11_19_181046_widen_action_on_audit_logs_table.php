<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas kolom audit_logs.action dari VARCHAR(20) ke VARCHAR(255).
 *
 * Latar belakang: cron menulis deskripsi aksi (mis. "Cron: Menandai 14 siswa
 * ALPHA/BDR pada 2026-11-19") yang melebihi 20 karakter sehingga insert gagal
 * dengan SQLSTATE[22001] "Data too long for column 'action'". Aksi lain tetap
 * memakai label pendek seperti CREATE/UPDATE/APPROVE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 255)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 20)->change();
        });
    }
};

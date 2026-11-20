<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom legacy `absensi_izin.verifikasi` dibuat NOT NULL tanpa DEFAULT oleh
 * skema CI lama, sehingga insert baru ditolak MySQL strict mode dengan
 * "Field 'verifikasi' doesn't have a default value". Tidak ada kode yang
 * membaca kolom ini; jadikan nullable agar insert tak perlu mengisinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('absensi_izin')
            || ! Schema::hasColumn('absensi_izin', 'verifikasi')) {
            return;
        }

        Schema::table('absensi_izin', function (Blueprint $table) {
            $table->string('verifikasi', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('absensi_izin')
            || ! Schema::hasColumn('absensi_izin', 'verifikasi')) {
            return;
        }

        // Kembali ke NOT NULL hanya mungkin bila tak ada baris NULL.
        DB::table('absensi_izin')->whereNull('verifikasi')->update(['verifikasi' => '']);

        Schema::table('absensi_izin', function (Blueprint $table) {
            $table->string('verifikasi', 255)->nullable(false)->change();
        });
    }
};

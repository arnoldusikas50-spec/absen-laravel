<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas kolom kode_sekolah dari VARCHAR(10) ke VARCHAR(50).
 *
 * Latar belakang: migration awal (2026_11_09_000056) mendefinisikan kolom
 * sebagai VARCHAR(10), sedangkan lazy-migration ensureNomorSettingColumns()
 * di SettingsController membuat kolom dengan VARCHAR(50) pada instance yang
 * belum pernah migrate. Perbedaan ini menyebabkan skema yang tidak konsisten
 * antar-deployment dan nilai kode_sekolah yang lebih dari 10 karakter terpotong
 * diam-diam di instance yang sudah migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting')) {
            return;
        }

        Schema::table('setting', function (Blueprint $table) {
            $table->string('kode_sekolah', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting')) {
            return;
        }

        Schema::table('setting', function (Blueprint $table) {
            $table->string('kode_sekolah', 10)->nullable()->change();
        });
    }
};

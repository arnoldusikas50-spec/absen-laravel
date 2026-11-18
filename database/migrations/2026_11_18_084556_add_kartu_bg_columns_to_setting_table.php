<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Latar kartu pelajar portrait (PNG) yang diunggah lewat /settings.
     * Kosong = template vektor bawaan yang tetap dipakai.
     */
    public function up(): void
    {
        if (Schema::hasTable('setting') && ! Schema::hasColumn('setting', 'kartu_bg_depan')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->string('kartu_bg_depan')->nullable()->after('banner3');
            });
        }

        if (Schema::hasTable('setting') && ! Schema::hasColumn('setting', 'kartu_bg_belakang')) {
            Schema::table('setting', function (Blueprint $table) {
                $table->string('kartu_bg_belakang')->nullable()->after('kartu_bg_depan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('setting')) {
            Schema::table('setting', function (Blueprint $table) {
                foreach (['kartu_bg_depan', 'kartu_bg_belakang'] as $col) {
                    if (Schema::hasColumn('setting', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

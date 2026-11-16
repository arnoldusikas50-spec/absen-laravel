<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('master_siswa') && ! Schema::hasColumn('master_siswa', 'nomor_rfid')) {
            Schema::table('master_siswa', function (Blueprint $table) {
                $table->string('nomor_rfid', 64)->nullable()->index();
            });
        }

        if (Schema::hasTable('master_guru') && ! Schema::hasColumn('master_guru', 'nomor_rfid')) {
            Schema::table('master_guru', function (Blueprint $table) {
                $table->string('nomor_rfid', 64)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('master_siswa') && Schema::hasColumn('master_siswa', 'nomor_rfid')) {
            Schema::table('master_siswa', function (Blueprint $table) {
                $table->dropColumn('nomor_rfid');
            });
        }

        if (Schema::hasTable('master_guru') && Schema::hasColumn('master_guru', 'nomor_rfid')) {
            Schema::table('master_guru', function (Blueprint $table) {
                $table->dropColumn('nomor_rfid');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_guru', function (Blueprint $table) {
            // Pendidikan terakhir guru (mis. "S1 Pendidikan Bahasa Indonesia").
            // Dipakai di sisi belakang kartu guru.
            if (! Schema::hasColumn('master_guru', 'pendidikan')) {
                $table->string('pendidikan', 100)->nullable()->after('tgs_tambahan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_guru', function (Blueprint $table) {
            if (Schema::hasColumn('master_guru', 'pendidikan')) {
                $table->dropColumn('pendidikan');
            }
        });
    }
};

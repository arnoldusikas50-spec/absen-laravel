<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260923000001_CreatePoinSiswaTables.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poin_siswa_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->integer('id_siswa');
            $table->integer('id_kelas')->nullable();
            $table->integer('poin')->default(0)->comment('Negatif untuk pelanggaran, positif untuk penghargaan');
            $table->unsignedInteger('kategori_id')->nullable();
            $table->enum('tipe', ['PELANGGARAN', 'PENGHARGAAN', 'KOREKSI', 'PRESENSI'])->default('PELANGGARAN');
            $table->string('keterangan', 255)->nullable();
            $table->string('ref_id', 80)->nullable();
            $table->integer('created_by')->nullable()->comment('users.id pencatat');
            $table->dateTime('created_at')->nullable();
            $table->unique('ref_id');
            $table->index('id_siswa');
            $table->index('tipe');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poin_siswa_history');
    }
};

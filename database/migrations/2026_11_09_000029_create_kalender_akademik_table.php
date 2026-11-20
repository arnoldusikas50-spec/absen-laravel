<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260614000006_CreateKalenderAkademikTable.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_akademik', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->string('judul', 255);
            $table->text('deskripsi')->nullable();
            $table->enum('tipe', ['LIBUR', 'UJIAN', 'EVENT', 'RAPAT', 'DINAS', 'LAINNYA'])->default('EVENT');
            $table->string('warna', 7)->default('#3b82f6');
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('tgl_mulai');
            $table->index('tipe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_akademik');
    }
};

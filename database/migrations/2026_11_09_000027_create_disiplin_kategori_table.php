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
        Schema::create('disiplin_kategori', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama', 100);
            $table->enum('jenis', ['PELANGGARAN', 'PENGHARGAAN'])->default('PELANGGARAN');
            $table->integer('bobot')->default(5)->comment('Besar poin (positif); arah +/- ikut jenis');
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('jenis');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disiplin_kategori');
    }
};

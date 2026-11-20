<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260614000007_CreateRewardTables.php
 *  - 20260615000001_AddNominalToRewards.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->integer('poin_dibutuhkan')->default(0);
            $table->integer('stok')->default(0);
            $table->string('gambar', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->decimal('nominal', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};

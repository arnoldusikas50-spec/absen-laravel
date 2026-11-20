<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 20260614000007_CreateRewardTables.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poin_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->integer('poin')->default(0);
            $table->string('tipe', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('ref_id', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index('user_id');
            $table->index('tipe');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poin_history');
    }
};

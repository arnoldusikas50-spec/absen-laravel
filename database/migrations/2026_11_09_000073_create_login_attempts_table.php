<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `login_attempts` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('ip_address', 45);
            $table->string('login', 100);
            $table->unsignedInteger('time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel sessions standar Laravel.
     *
     * Migrasi default 0001_01_01_000000_create_users_table.php dihapus saat
     * porting karena ia membuat tabel `users` berskema auth Laravel yang
     * salah untuk aplikasi ini (skema CI4 `users` tidak ada di migrasi).
     * Namun migrasi itu juga membuat tabel `sessions`, yang dibutuhkan
     * karena SESSION_DRIVER=database — jadi dibuatkan migrasi sendiri.
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

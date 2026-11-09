<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `users` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('ip_address', 45);
            $table->string('username', 100)->nullable();
            $table->string('password', 255);
            $table->string('email', 254)->nullable();
            $table->string('activation_selector', 255)->nullable();
            $table->string('activation_code', 255)->nullable();
            $table->string('forgotten_password_selector', 255)->nullable();
            $table->string('forgotten_password_code', 255)->nullable();
            $table->unsignedInteger('forgotten_password_time')->nullable();
            $table->string('remember_selector', 255)->nullable();
            $table->string('remember_code', 255)->nullable();
            $table->unsignedInteger('created_on');
            $table->unsignedInteger('last_login')->nullable();
            $table->unsignedTinyInteger('active')->nullable();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('company', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('foto', 250)->nullable();
            $table->string('jabatan', 50)->nullable();
            $table->text('face_descriptor')->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->unique('id', $isMysql ? 'id_user' : 'users_id_user_index');
            $table->unique('username', 'username_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

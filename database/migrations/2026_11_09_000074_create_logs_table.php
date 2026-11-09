<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `logs` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 * Nama index yang sama di beberapa tabel dump (mis. idx_sync_uuid) ditulis kondisional: nama persis dump di MySQL, nama berawalan nama tabel di SQLite (nama index harus unik se-database di SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id_log');
            $table->integer('id_user')->nullable();
            $table->integer('aktivitas')->default(0)->comment('1=Login, 2=Logout, 3=Create, 4=Read, 5=Update, 6=Delete');
            $table->string('keterangan', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('waktu')->nullable();
            // Nama index di dump MySQL boleh sama antar tabel, tetapi SQLite
            // mengharuskan nama index unik se-database.
            $isMysql = Schema::getConnection()->getDriverName() === 'mysql';
            $table->index('id_user', $isMysql ? 'id_user' : 'logs_id_user_index');
            $table->index('waktu', 'waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

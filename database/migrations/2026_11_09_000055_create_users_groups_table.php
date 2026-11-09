<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `users_groups` — skema dari database produksi (dump absen.sql, MariaDB 10.11.10, 9 Nov 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedMediumInteger('group_id');
            $table->unique(['user_id', 'group_id'], 'uc_users_groups');
            $table->index('user_id', 'fk_users_groups_users1_idx');
            $table->index('group_id', 'fk_users_groups_groups1_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_groups');
    }
};

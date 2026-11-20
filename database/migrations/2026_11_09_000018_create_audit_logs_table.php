<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  - 2026-02-10-214538_CreateAuditLogsTable.php
 *
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->string('table_name', 50);
            $table->unsignedInteger('record_id')->nullable();
            $table->unsignedInteger('target_user_id')->nullable()->comment('ID user pemilik data (misal: guru)');
            $table->unsignedInteger('actor_id')->nullable()->comment('ID user yang melakukan perubahan (misal: admin)');
            $table->string('action', 20);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index('target_user_id');
            $table->index('record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

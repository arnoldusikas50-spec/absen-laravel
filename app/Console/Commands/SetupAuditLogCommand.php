<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\SetupAuditLog (php spark db:setup_audit).
 *
 * Membuat tabel audit_logs secara manual (padanan Forge CI4, memakai
 * Schema builder Laravel). Skema kolom dipertahankan sama persis.
 */
class SetupAuditLogCommand extends Command
{
    protected $signature = 'db:setup_audit';

    protected $description = 'Creates the audit_logs table manually.';

    public function handle(): int
    {
        if (Schema::hasTable('audit_logs')) {
            $this->warn('Table audit_logs already exists.');

            return self::SUCCESS;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('table_name', 50);
            $table->unsignedInteger('record_id')->nullable();
            $table->unsignedInteger('target_user_id')->nullable()->comment('ID user pemilik data (misal: guru)');
            $table->unsignedInteger('actor_id')->nullable()->comment('ID user yang melakukan perubahan (misal: admin)');
            $table->string('action', 20); // CREATE, UPDATE, DELETE
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index('target_user_id');
            $table->index('record_id');
        });

        $this->info('Table audit_logs created successfully.');

        return self::SUCCESS;
    }
}

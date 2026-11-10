<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus seluruh fitur tracer study.
 *
 * Fitur ini belum pernah berjalan di aplikasi (tidak ada route, controller,
 * atau view; tabelnya juga kosong), jadi seluruh artefaknya dibuang:
 *  - tabel `tracer_study_responses` (jawaban kuesioner lulusan)
 *  - tabel `tracer_study_settings` (pengaturan tracer study)
 *  - tabel `alumni` (indeks data lulusan, sekaligus penyimpan flag
 *    `tracer_filled` / `tracer_filled_at`)
 *
 * Migrasi create asal (2026_11_09_000017, 000049, 000050) ikut dihapus file-nya,
 * jadi baris `migrations` untuk ketiganya dibersihkan di sini supaya status
 * migrasi tetap konsisten.
 */
return new class extends Migration
{
    /** Migrasi asal yang dihapus; dibersihkan dari tabel `migrations`. */
    private const MIGRASI_ASAL = [
        '2026_11_09_000017_create_alumni_table',
        '2026_11_09_000049_create_tracer_study_responses_table',
        '2026_11_09_000050_create_tracer_study_settings_table',
    ];

    public function up(): void
    {
        Schema::dropIfExists('tracer_study_responses');
        Schema::dropIfExists('tracer_study_settings');
        Schema::dropIfExists('alumni');

        DB::table('migrations')
            ->whereIn('migration', self::MIGRASI_ASAL)
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('alumni')) {
            Schema::create('alumni', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->increments('id');
                $table->string('nama', 150);
                $table->string('nisn', 20)->nullable();
                $table->year('tahun_lulus')->nullable();
                $table->string('email', 150);
                $table->string('no_hp', 20)->nullable();
                $table->string('tempat_lahir', 100)->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
                $table->text('alamat')->nullable();
                $table->string('jurusan', 100)->nullable();
                $table->string('foto', 255)->nullable();
                $table->boolean('status')->default(0)->comment('0: Pending, 1: Verified');
                $table->string('token', 64)->nullable();
                $table->boolean('tracer_filled')->default(0);
                $table->dateTime('tracer_filled_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index('email');
                $table->unique('token');
            });
        }

        if (! Schema::hasTable('tracer_study_responses')) {
            Schema::create('tracer_study_responses', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->increments('id');
                $table->unsignedInteger('id_alumni')->nullable();
                $table->string('nama', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->year('tahun_lulus')->nullable();
                $table->string('status_kegiatan', 50)->nullable();
                $table->string('nama_institusi', 255)->nullable();
                $table->string('bidang_pekerjaan', 100)->nullable();
                $table->string('jabatan', 100)->nullable();
                $table->string('gaji_range', 50)->nullable();
                $table->integer('waktu_tunggu_kerja')->nullable()->comment('Months');
                $table->string('kesesuaian_bidang', 50)->nullable();
                $table->string('pendidikan_lanjut', 50)->nullable();
                $table->string('nama_kampus', 200)->nullable();
                $table->string('program_studi', 150)->nullable();
                $table->tinyInteger('kepuasan_sekolah')->nullable()->comment('1-5 rating');
                $table->text('saran')->nullable();
                $table->json('jawaban_detail')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index('id_alumni');
                $table->index('email');
                $table->index('tahun_lulus');
                $table->index('status_kegiatan');
            });
        }

        if (! Schema::hasTable('tracer_study_settings')) {
            Schema::create('tracer_study_settings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->increments('id');
                $table->string('key', 100);
                $table->text('value')->nullable();
                $table->string('description', 255)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique('key');
            });
        }
    }
};

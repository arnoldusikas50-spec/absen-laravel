<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari migrasi CodeIgniter 4 — tabel `siswa_biodata`.
 * Skema konsolidasi (hasil akhir) dari migrasi-migrasi CI4 berikut:
 *  - 2026-01-17-004700_CreateSiswaBiodataTable.php
 *
 * Catatan: kolom dibuat dengan tipe MySQL yang sama seperti CI4
 * (INT/VARCHAR/TEXT/DATETIME/DATE/DECIMAL/TINYINT(1)/ENUM/JSON).
 * Tidak ada foreign key di migrasi CI4, jadi tidak ada FK di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_biodata', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id');
            $table->unsignedInteger('id_siswa');
            $table->string('no_registrasi_akta', 50)->nullable();
            $table->string('nik_kitas', 30)->nullable()->comment('NIK/KITAS untuk WNA');
            $table->text('berkebutuhan_khusus')->nullable()->comment('JSON array of berkebutuhan khusus codes');
            $table->string('nama_dusun', 100)->nullable();
            $table->string('lintang', 20)->nullable()->comment('Latitude koordinat');
            $table->string('bujur', 20)->nullable()->comment('Longitude koordinat');
            $table->string('tempat_tinggal', 5)->nullable()->comment('Kode tempat tinggal (01-05)');
            $table->string('moda_transportasi', 5)->nullable()->comment('Kode moda transportasi');
            $table->tinyInteger('anak_keberapa')->nullable();
            $table->smallInteger('tinggi_badan')->nullable()->comment('Dalam cm');
            $table->smallInteger('berat_badan')->nullable()->comment('Dalam kg');
            $table->smallInteger('lingkar_kepala')->nullable()->comment('Dalam cm');
            $table->string('jarak_ke_sekolah', 30)->nullable();
            $table->decimal('jarak_km', 5, 2)->nullable();
            $table->tinyInteger('waktu_tempuh_jam')->nullable();
            $table->tinyInteger('waktu_tempuh_menit')->nullable();
            $table->tinyInteger('jumlah_saudara')->nullable();
            $table->string('kompetensi_keahlian', 100)->nullable();
            $table->string('jenis_pendaftaran', 5)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('sekolah_asal', 150)->nullable();
            $table->string('no_peserta_un', 25)->nullable();
            $table->string('no_seri_ijazah', 20)->nullable();
            $table->string('no_skhun', 20)->nullable();
            $table->string('keluar_karena', 5)->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->text('alasan_keluar')->nullable();
            $table->string('jenis_kesejahteraan', 5)->nullable();
            $table->string('no_kartu_kesejahteraan', 30)->nullable();
            $table->string('nama_di_kartu', 100)->nullable();
            $table->enum('punya_kip', ['Ya', 'Tidak'])->default('Tidak');
            $table->enum('peserta_didik_kip', ['Ya', 'Tidak'])->default('Tidak');
            $table->string('alasan_menolak_pip', 5)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('id_siswa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_biodata');
    }
};

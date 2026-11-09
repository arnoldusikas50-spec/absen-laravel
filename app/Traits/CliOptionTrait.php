<?php

namespace App\Traits;

/**
 * CliOptionTrait - Pembaca opsi baris perintah yang andal untuk perintah artisan.
 *
 * Port dari CodeIgniter 4 (Spark) ke Laravel 13 (Artisan).
 *
 * Di Artisan, `$this->option('keep')` sudah menangani `--keep=999` maupun
 * `--keep 999` dengan benar, jadi jalur utama memakai itu. Fallback parsing
 * `$_SERVER['argv']` dipakai bila trait dipakai di luar command (mis. saat
 * perintah dijalankan dari kode tanpa konteks command).
 *
 * Perilaku yang dipertahankan dari versi CI4:
 *   - `--keep=999` dan `--keep 999` sama-sama terbaca.
 *   - optInt(): nilai rusak (< 1 / non-numerik) kembali ke bawaan dengan
 *     peringatan, bukan diam-diam jadi 1.
 *   - optBool(): `--dry`, `--dry=1`, `--dry=true` aktif; `--dry=0` tidak.
 */
trait CliOptionTrait
{
    /**
     * Nilai mentah sebuah opsi: string | true (flag tanpa nilai) | null (tidak ada).
     */
    protected function opt(string $nama)
    {
        // Jalur Artisan: option() sudah memisah "=" dengan benar.
        if (method_exists($this, 'option')) {
            try {
                $nilai = $this->option($nama);
                if ($nilai !== null) {
                    return $nilai;
                }
            } catch (\Throwable $e) {
                // Opsi tidak terdaftar di signature — lanjut ke parsing argv.
            }
        }

        $argv = $_SERVER['argv'] ?? [];
        foreach ($argv as $i => $arg) {
            if ($arg === '--' . $nama) {
                $next = $argv[$i + 1] ?? null;
                if ($next !== null && !str_starts_with((string) $next, '-')) {
                    return $next;
                }
                return true;
            }
            if (str_starts_with($arg, '--' . $nama . '=')) {
                return substr($arg, strlen('--' . $nama . '='));
            }
        }

        return null;
    }

    /**
     * Opsi integer. Tanpa nilai, non-numerik, atau < 1 tidak boleh diam-diam
     * jadi 1 — retensi 1 hari berarti menghapus hampir semua yang seharusnya
     * disimpan — jadi nilai rusak kembali ke bawaan dengan peringatan.
     */
    protected function optInt(string $nama, int $bawaan): int
    {
        $nilai = $this->opt($nama);

        if ($nilai === null || $nilai === true) {
            return $bawaan;
        }

        if (! is_numeric($nilai) || (int) $nilai < 1) {
            $this->tulisPeringatan("--{$nama}=\"{$nilai}\" bukan angka ≥ 1, pakai bawaan {$bawaan}.");

            return $bawaan;
        }

        return (int) $nilai;
    }

    /**
     * Opsi flag. `--dry`, `--dry=1`, `--dry=true` aktif; `--dry=0` tidak.
     */
    protected function optBool(string $nama): bool
    {
        $nilai = $this->opt($nama);

        if ($nilai === null) {
            return false;
        }

        return $nilai === true || filter_var($nilai, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Tulis peringatan ke output command bila ada, kalau tidak ke STDERR.
     */
    private function tulisPeringatan(string $pesan): void
    {
        if (method_exists($this, 'warn')) {
            $this->warn($pesan);
            return;
        }

        fwrite(STDERR, $pesan . PHP_EOL);
    }
}

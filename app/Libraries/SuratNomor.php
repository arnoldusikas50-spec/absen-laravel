<?php

namespace App\Libraries;

use Illuminate\Support\Facades\DB;

/**
 * SuratNomor — generator nomor surat otomatis.
 *
 * Port dari CodeIgniter 4 ke Laravel 13 (\Config\Database -> DB facade).
 * Format default: SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}
 */
class SuratNomor
{
    public const DEFAULT_FORMAT = 'SP/421.3/{kode_sekolah}/{nomor_urut}/{tahun}';

    public const DEFAULT_PADDING = 3;

    public const DEFAULT_KODE_SEKOLAH = 'SMAN2.AT';

    public static ?object $rowCache = null;

    /**
     * Format template surat.
     */
    public static function formatTemplate(): string
    {
        $row = self::row();
        $tpl = trim((string) ($row->surat_teguran_format ?? ''));

        if ($tpl === '') {
            $tpl = self::DEFAULT_FORMAT;
        }

        return $tpl;
    }

    /**
     * Padding nomor urut.
     */
    public static function padding(): int
    {
        $row = self::row();
        $pad = (int) ($row->surat_teguran_padding ?? 0);

        return $pad > 0 ? $pad : self::DEFAULT_PADDING;
    }

    /**
     * Kode sekolah untuk nomor surat.
     */
    public static function kodeSekolah(): string
    {
        $row = self::row();
        $kode = trim((string) ($row->kode_sekolah ?? ''));

        return $kode !== '' ? $kode : self::DEFAULT_KODE_SEKOLAH;
    }

    /**
     * Bangun nomor surat dari template.
     *
     * @param  object|null  $setting  Baris setting; jika null memakai baris dari tabel setting.
     */
    public static function build(
        int $nomorUrut,
        int $bulan,
        int $tahun,
        ?object $setting = null,
        int $level = 0,
        ?string $tanggal = null
    ): string {
        $setting ??= self::row();

        $template = self::templateFor($setting);
        $pad = self::paddingFor($setting);

        $bulan = $bulan > 0 ? $bulan : (int) date('n');
        $tahun = $tahun > 0 ? $tahun : (int) date('Y');

        $nomor = (string) $nomorUrut;
        if ($pad > 0) {
            $nomor = str_pad($nomor, $pad, '0', STR_PAD_LEFT);
        }

        $placeholders = [
            'kode_sekolah' => self::kodeSekolahFor($setting),
            'sekolah' => (string) ($setting->sekolah ?? ''),
            'npsn' => (string) ($setting->npsn ?? ''),
            'nss' => (string) ($setting->nss ?? ''),
            'nip_kepsek' => (string) ($setting->nip ?? $setting->nip_kepsek ?? ''),
            'kepsek' => (string) ($setting->kepsek ?? ''),
            'nomor' => $nomor,
            'nomor_urut' => $nomor,
            'bulan' => str_pad((string) $bulan, 2, '0', STR_PAD_LEFT),
            'bulan_romawi' => self::bulanRomawi($bulan),
            'bulan_nama' => self::bulanNama($bulan),
            'tahun' => (string) $tahun,
            'tahun_2' => substr((string) $tahun, -2),
            'level' => (string) $level,
            'tanggal' => $tanggal ?: date('Y-m-d'),
        ];

        return self::renderTemplate($template, $placeholders);
    }

    /**
     * Preview nomor surat dengan nomor urut contoh.
     */
    public static function preview(
        ?string $template = null,
        ?int $padding = null,
        ?string $kodeSekolah = null,
        int $nomorUrut = 1
    ): string {
        $setting = (object) array_merge(
            (array) (self::row() ?? []),
            [
                'surat_teguran_format' => $template,
                'surat_teguran_padding' => $padding,
                'kode_sekolah' => $kodeSekolah,
            ]
        );

        return self::build($nomorUrut, (int) date('n'), (int) date('Y'), $setting);
    }

    /**
     * Daftar placeholder yang tersedia.
     */
    public static function placeholders(): array
    {
        return [
            'kode_sekolah' => 'Kode sekolah (mis. SMAN2.AT)',
            'sekolah' => 'Nama sekolah',
            'npsn' => 'NPSN sekolah',
            'nss' => 'NSS sekolah',
            'nip_kepsek' => 'NIP Kepala Sekolah',
            'kepsek' => 'Nama Kepala Sekolah',
            'nomor' => 'Nomor urut (sama dengan nomor_urut)',
            'nomor_urut' => 'Nomor urut dengan padding',
            'bulan' => 'Bulan (01-12)',
            'bulan_romawi' => 'Bulan Romawi (I-XII)',
            'bulan_nama' => 'Nama bulan (Januari, dst)',
            'tahun' => 'Tahun (2025)',
            'tahun_2' => 'Tahun 2 digit (25)',
            'level' => 'Level surat teguran (mis. I, II, III)',
            'tanggal' => 'Tanggal (YYYY-MM-DD)',
        ];
    }

    /**
     * Sanitasi template: hapus karakter berbahaya, biarkan placeholder.
     */
    public static function sanitizeTemplate(?string $template): string
    {
        $template = (string) $template;

        // Hanya izinkan karakter aman + placeholder {nama}
        $template = preg_replace('/[^\w\s\/\.\-\{\}]/u', '', $template) ?? $template;

        return trim($template);
    }

    /**
     * Flush cache baris setting.
     */
    public static function flushCache(): void
    {
        self::$rowCache = null;
    }

    /**
     * Template efektif dari setting (fallback ke baris DB / bawaan).
     */
    private static function templateFor(?object $setting): string
    {
        $template = trim((string) ($setting->surat_teguran_format ?? ''));

        return $template !== '' ? $template : self::formatTemplate();
    }

    /**
     * Padding efektif dari setting (fallback ke baris DB / bawaan).
     */
    private static function paddingFor(?object $setting): int
    {
        $padding = (int) ($setting->surat_teguran_padding ?? 0);

        return $padding > 0 ? $padding : self::padding();
    }

    /**
     * Kode sekolah efektif dari setting (fallback ke baris DB / bawaan).
     */
    private static function kodeSekolahFor(?object $setting): string
    {
        $kode = trim((string) ($setting->kode_sekolah ?? ''));

        return $kode !== '' ? $kode : self::kodeSekolah();
    }

    /**
     * Render template dengan placeholder.
     */
    private static function renderTemplate(string $template, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        return $template;
    }

    /**
     * Ambil baris setting (cache sekali per request).
     */
    private static function row(): ?object
    {
        if (self::$rowCache !== null) {
            return self::$rowCache;
        }

        try {
            self::$rowCache = DB::table('setting')->first();
        } catch (\Throwable $e) {
            self::$rowCache = null;
        }

        return self::$rowCache;
    }

    private static function bulanRomawi(int $bulan): string
    {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romawi[$bulan] ?? '';
    }

    private static function bulanNama(int $bulan): string
    {
        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $nama[$bulan] ?? '';
    }
}

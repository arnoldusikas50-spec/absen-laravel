<?php

namespace App\Libraries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Theme — Palet warna dinamis (Tailwind) berdasarkan setting theme_preset.
 *
 * Port dari CodeIgniter 4 ke Laravel 13 (\Config\Database -> DB/Schema facade).
 */
class Theme
{
    public const DEFAULT_PRESET = 'default';

    /**
     * Geseran hue (derajat) untuk aksen tema kustom.
     */
    public const CUSTOM_ACCENT_HUE_SHIFT = 32.0;

    /**
     * Palet dasar Tailwind (50-900). Source: tailwindcss.com/docs/colors.
     */
    protected static array $palettes = [
        'rose' => ['50' => '#fff1f2', '100' => '#ffe4e6', '200' => '#fecdd3', '300' => '#fda4af', '400' => '#fb7185', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '800' => '#9f1239', '900' => '#881337'],
        'blue' => ['50' => '#eff6ff', '100' => '#dbeafe', '200' => '#bfdbfe', '300' => '#93c5fd', '400' => '#60a5fa', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8', '800' => '#1e40af', '900' => '#1e3a8a'],
        'violet' => ['50' => '#f5f3ff', '100' => '#ede9fe', '200' => '#ddd6fe', '300' => '#c4b5fd', '400' => '#a78bfa', '500' => '#8b5cf6', '600' => '#7c3aed', '700' => '#6d28d9', '800' => '#5b21b6', '900' => '#4c1d95'],
        'sky' => ['50' => '#f0f9ff', '100' => '#e0f2fe', '200' => '#bae6fd', '300' => '#7dd3fc', '400' => '#38bdf8', '500' => '#0ea5e9', '600' => '#0284c7', '700' => '#0369a1', '800' => '#075985', '900' => '#0c4a6e'],
        'emerald' => ['50' => '#ecfdf5', '100' => '#d1fae5', '200' => '#a7f3d0', '300' => '#6ee7b7', '400' => '#34d399', '500' => '#10b981', '600' => '#059669', '700' => '#047857', '800' => '#065f46', '900' => '#064e3b'],
        'orange' => ['50' => '#fff7ed', '100' => '#ffedd5', '200' => '#fed7aa', '300' => '#fdba74', '400' => '#fb923c', '500' => '#f97316', '600' => '#ea580c', '700' => '#c2410c', '800' => '#9a3412', '900' => '#7c2d12'],
        'indigo' => ['50' => '#eef2ff', '100' => '#e0e7ff', '200' => '#c7d2fe', '300' => '#a5b4fc', '400' => '#818cf8', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '800' => '#3730a3', '900' => '#312e81'],
        'purple' => ['50' => '#faf5ff', '100' => '#f3e8ff', '200' => '#e9d5ff', '300' => '#d8b4fe', '400' => '#c084fc', '500' => '#a855f7', '600' => '#9333ea', '700' => '#7e22ce', '800' => '#6b21a8', '900' => '#581c87'],
        'fuchsia' => ['50' => '#fdf4ff', '100' => '#fae8ff', '200' => '#f5d0fe', '300' => '#f0abfc', '400' => '#e879f9', '500' => '#d946ef', '600' => '#c026d3', '700' => '#a21caf', '800' => '#86198f', '900' => '#701a75'],
        'teal' => ['50' => '#f0fdfa', '100' => '#ccfbf1', '200' => '#99f6e4', '300' => '#5eead4', '400' => '#2dd4bf', '500' => '#14b8a6', '600' => '#0d9488', '700' => '#0f766e', '800' => '#115e59', '900' => '#134e4a'],
        'pink' => ['50' => '#fdf2f8', '100' => '#fce7f3', '200' => '#fbcfe8', '300' => '#f9a8d4', '400' => '#f472b6', '500' => '#ec4899', '600' => '#db2777', '700' => '#be185d', '800' => '#9d174d', '900' => '#831843'],
        'cyan' => ['50' => '#ecfeff', '100' => '#cffafe', '200' => '#a5f3fc', '300' => '#67e8f9', '400' => '#22d3ee', '500' => '#06b6d4', '600' => '#0891b2', '700' => '#0e7490', '800' => '#155e75', '900' => '#164e63'],
    ];

    /**
     * Definisi preset: [label, desc, primary, accent].
     */
    /**
     * Setiap preset memasangkan dua hue bertetangga (jarak <= 60 derajat) agar
     * gradien primary -> accent tetap mulus dan tidak keruh di tengah.
     */
    protected static array $presets = [
        'default' => ['label' => 'Standar (Rose + Fuchsia)', 'desc' => 'Merah muda ke magenta; hangat, modern, dan profesional.', 'primary' => 'rose', 'accent' => 'fuchsia'],
        'ocean' => ['label' => 'Samudra (Sky + Indigo)', 'desc' => 'Biru terang ke indigo; segar dan menenangkan.', 'primary' => 'sky', 'accent' => 'indigo'],
        'emerald' => ['label' => 'Zamrud (Emerald + Cyan)', 'desc' => 'Hijau ke aqua; natural dan ramah mata.', 'primary' => 'emerald', 'accent' => 'cyan'],
        'royal' => ['label' => 'Kerajaan (Violet + Blue)', 'desc' => 'Ungu ke biru; elegan dan berwibawa.', 'primary' => 'violet', 'accent' => 'blue'],
        'sunset' => ['label' => 'Senja (Orange + Rose)', 'desc' => 'Oranye hangat ke merah muda; energik dan bersemangat.', 'primary' => 'orange', 'accent' => 'rose'],
        'rose' => ['label' => 'Merah Muda (Pink + Violet)', 'desc' => 'Merah muda lembut ke ungu; gradien blush yang modern.', 'primary' => 'pink', 'accent' => 'violet'],
        'custom' => ['label' => 'Kustom (warna manual)', 'desc' => 'Pilih warna dasar sendiri; palet dan gradien dibuat otomatis.', 'primary' => 'rose', 'accent' => 'fuchsia'],
    ];

    protected static ?array $cache = null;

    /**
     * Daftar key preset yang tersedia.
     */
    public static function presetKeys(): array
    {
        return array_keys(self::$presets);
    }

    /**
     * Daftar preset untuk dropdown: [key => label].
     */
    public static function presetOptions(): array
    {
        $out = [];
        foreach (self::$presets as $key => $def) {
            $out[$key] = $def['label'];
        }

        return $out;
    }

    /**
     * Kartu preset untuk pemilih tema di halaman pengaturan.
     *
     * @return array<string, array{label: string, desc: string, colors: array{brand: string, primary: string, accent: string}}>
     */
    public static function presetCards(): array
    {
        $out = [];
        foreach (self::$presets as $key => $def) {
            // Kartu "Custom" dirender terpisah oleh view karena memakai
            // warna dasar pilihan pengguna, bukan palet statis.
            if ($key === 'custom') {
                continue;
            }

            $primary = self::$palettes[$def['primary']];
            $accent = self::$palettes[$def['accent']];

            $out[$key] = [
                'label' => $def['label'],
                'desc' => $def['desc'],
                'colors' => [
                    'brand' => $primary['600'],
                    'primary' => $primary['500'],
                    'accent' => $accent['500'],
                ],
            ];
        }

        return $out;
    }

    /**
     * Palet aktif saat ini (preset + custom dari setting).
     */
    public static function current(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $preset = self::DEFAULT_PRESET;
        $custom = null;

        try {
            if (Schema::hasTable('setting')) {
                $row = DB::table('setting')->first();
                if ($row) {
                    $preset = trim((string) ($row->theme_preset ?? '')) ?: self::DEFAULT_PRESET;
                    $custom = $row->theme_custom ?? null;
                }
            }
        } catch (\Throwable $e) {
        }

        return self::$cache = self::resolve($preset, $custom);
    }

    /**
     * Hapus cache palet (dipakai setelah setting disimpan).
     */
    public static function flushCache(): void
    {
        self::$cache = null;
    }

    /**
     * Resolve preset + custom hex menjadi struktur palet lengkap.
     */
    public static function resolve(string $preset, $custom = null): array
    {
        if (! isset(self::$presets[$preset])) {
            $preset = self::DEFAULT_PRESET;
        }

        $def = self::$presets[$preset];
        $customHex = self::normalizeHex(is_string($custom) ? $custom : null);

        if ($preset === 'custom') {
            // theme_custom disimpan sebagai hex tunggal; tetap dukung format
            // JSON lama ({primary, accent}) agar data lama tidak rusak.
            if ($customHex === null && is_string($custom) && $custom !== '') {
                $decoded = json_decode($custom, true);
                if (is_array($decoded)) {
                    $customHex = self::normalizeHex($decoded['primary'] ?? null);
                }
            }

            if ($customHex !== null) {
                $palette = self::paletteFromHex($customHex);
                if ($palette !== null) {
                    // Aksen digeser hue-nya agar gradien primary -> accent tetap
                    // dua nada (bukan warna kembar yang terlihat datar).
                    $accent = self::paletteFromHex(self::shiftHue($customHex, self::CUSTOM_ACCENT_HUE_SHIFT))
                        ?? $palette;

                    return [
                        'preset' => 'custom',
                        'label' => $def['label'],
                        'custom' => $customHex,
                        'primary' => $palette,
                        'accent' => $accent,
                    ];
                }
            }
        }

        return [
            'preset' => $preset,
            'label' => $def['label'],
            'custom' => $customHex,
            'primary' => self::$palettes[$def['primary']],
            'accent' => self::$palettes[$def['accent']],
        ];
    }

    /**
     * Warna semantik untuk UI. Diambil dari shade palet primary/accent agar
     * konsisten dengan tema yang dipilih (bukan hardcoded).
     */
    protected static function semanticColors(array $theme): array
    {
        $p = $theme['primary'];
        $a = $theme['accent'];

        return [
            // ── Background / surface ──
            'surface' => '#ffffff',
            'surface-hover' => $p['50'],
            'surface-muted' => '#f8fafc',
            'surface-inverse' => '#0f172a',
            'surface-inverse-hover' => '#1e293b',

            // ── Border ──
            'border' => '#e2e8f0',
            'border-focus' => $p['500'],
            'border-muted' => '#cbd5e1',

            // ── Text ──
            'text-primary' => '#0f1729',
            'text-secondary' => '#475569',
            'text-muted' => '#94a3b8',
            'text-inverse' => '#ffffff',
            'text-success' => $p['700'] ?? '#047857',
            'text-warning' => $a['700'] ?? '#c2410c',
            'text-error' => '#dc2626',
            'text-info' => $p['700'] ?? '#1d4ed8',

            // ── Interactive ──
            'primary' => $p['600'],
            'primary-hover' => $p['700'],
            'primary-active' => $p['800'],
            'primary-subtle' => $p['50'],
            'accent' => $a['600'],
            'accent-hover' => $a['700'],
            'accent-active' => $a['800'],
            'accent-subtle' => $a['50'],
        ];
    }

    /**
     * CSS variables (:root) untuk dipakai di layout.
     *
     * Menghasilkan keluarga --primary-*, --accent-*, --indigo-*, --purple-*
     * (plus varian -rgb untuk utility Tailwind) agar nilainya sama dengan
     * pratinjau tema di halaman pengaturan, serta alias --theme-primary-* dan
     * --theme-accent-* untuk kompatibilitas. Ditambahkan variabel semantik
     * (--surface-*, --border-*, --text-*, --primary-*, --accent-*) agar layout
     * dapat mengikuti tema yang dipilih tanpa hardcoded warna.
     */
    public static function cssVars(?array $theme = null): string
    {
        $theme = $theme ?? self::current();

        $families = [
            'primary' => $theme['primary'],
            'accent' => $theme['accent'],
            'indigo' => self::$palettes['indigo'],
            'purple' => self::$palettes['purple'],
        ];

        $out = [];
        foreach ($families as $role => $palette) {
            foreach ($palette as $shade => $hex) {
                $out[] = "--{$role}-{$shade}: {$hex};";
                $out[] = '--'.$role.'-'.$shade.'-rgb: '.implode(' ', self::hexToRgb($hex)).';';

                if ($role === 'primary' || $role === 'accent') {
                    $out[] = "--theme-{$role}-{$shade}: {$hex};";
                }
            }
        }

        // ── Variabel semantik untuk UI layout ──
        $semantic = self::semanticColors($theme);
        foreach ($semantic as $name => $hex) {
            $out[] = "--{$name}: {$hex};";
            $out[] = '--'.$name.'-rgb: '.implode(' ', self::hexToRgb($hex)).';';
        }

        return implode(' ', $out);
    }

    /**
     * Alias --brand-* dari palet primary.
     *
     * Dipakai utility Tailwind (rgb(var(--brand-600-rgb) / alpha)) dan CSS
     * halaman (var(--brand-600)). Tanpa variabel ini warna brand di layout
     * menjadi tidak valid/tidak tampil.
     */
    public static function brandVars(?array $theme = null): string
    {
        $theme = $theme ?? self::current();
        $primary = $theme['primary'];

        $out = [];
        foreach ($primary as $shade => $hex) {
            $out[] = "--brand-{$shade}: {$hex};";
            $out[] = "--brand-{$shade}-rgb: ".implode(' ', self::hexToRgb($hex)).';';
        }

        $secondary = $primary['400'] ?? $primary['500'] ?? null;
        $tertiary = $primary['700'] ?? $primary['600'] ?? null;
        $base = $primary['600'] ?? null;

        if ($secondary !== null) {
            $out[] = "--brand-secondary: {$secondary};";
            $out[] = '--brand-secondary-rgb: '.implode(' ', self::hexToRgb($secondary)).';';
        }

        if ($tertiary !== null) {
            $out[] = "--brand-tertiary: {$tertiary};";
            $out[] = '--brand-tertiary-rgb: '.implode(' ', self::hexToRgb($tertiary)).';';
        }

        if ($base !== null) {
            $out[] = "--primary: {$base};";
            $out[] = '--primary-rgb: '.implode(', ', self::hexToRgb($base)).';';
        }

        return implode(' ', $out);
    }

    /**
     * Tag <style> lengkap dengan variabel tema.
     */
    public static function styleTag(?array $theme = null): string
    {
        $theme = $theme ?? self::current();

        return '<style>:root{'.self::cssVars($theme).self::brandVars($theme).'}</style>';
    }

    /**
     * Tag <style> khusus halaman login (background gradient).
     */
    public static function loginStyleTag(?array $theme = null): string
    {
        $theme = $theme ?? self::current();
        $p500 = $theme['primary']['500'] ?? '#f43f5e';
        $a500 = $theme['accent']['500'] ?? '#6366f1';
        $p600 = $theme['primary']['600'] ?? '#e11d48';
        $a600 = $theme['accent']['600'] ?? '#4f46e5';

        // Alias --brand-* dipakai halaman login (termasuk varian -rgb untuk
        // pola rgb(var(--brand-*-rgb) / alpha)). Tanpa ini tombol login
        // kehilangan warna latar sehingga tampak tidak terlihat.
        $brandVars = self::brandVars($theme);

        return '<style>'
            .':root{'.self::cssVars($theme).$brandVars.'}'
            .'.theme-login-bg{background:linear-gradient(135deg,'.$p600.' 0%,'.$a600.' 100%)}'
            .'.theme-login-btn{background:linear-gradient(135deg,'.$p500.','.$a500.')}'
            .'</style>';
    }

    /**
     * Meta theme-color untuk PWA / mobile browser.
     */
    public static function metaColor(string $area = 'primary', ?array $theme = null): string
    {
        $theme = $theme ?? self::current();

        return $theme[$area]['600'] ?? '#e11d48';
    }

    /**
     * Konfigurasi tema untuk JavaScript (client-side).
     */
    public static function clientConfig(?array $theme = null): array
    {
        $theme = $theme ?? self::current();

        return [
            'preset' => $theme['preset'],
            'primary' => $theme['primary'],
            'accent' => $theme['accent'],
            'presets' => self::clientPresets(),
            'semantic' => self::semanticColors($theme),
        ];
    }

    /**
     * Peta palet semua preset untuk pratinjau tema di sisi klien.
     *
     * Nilai secondary/tertiary di sini harus sama dengan Theme::brandVars()
     * (shade 400 dan 700 dari palet primary) agar pratinjau klien identik
     * dengan CSS yang dirender server.
     *
     * @return array<string, array{
     *     brand: array<string, string>,
     *     primary: array<string, string>,
     *     accent: array<string, string>,
     *     indigo: array<string, string>,
     *     purple: array<string, string>,
     *     secondary: string,
     *     tertiary: string
     * }>
     */
    protected static function clientPresets(): array
    {
        $out = [];
        foreach (self::$presets as $key => $def) {
            if ($key === 'custom') {
                continue;
            }

            $primary = self::$palettes[$def['primary']];
            $accent = self::$palettes[$def['accent']];

            // Variabel semantik agar pratinjau klien identik dengan CSS server.
            $semantic = self::semanticColors([
                'preset' => $key,
                'primary' => $primary,
                'accent' => $accent,
            ]);

            $out[$key] = [
                'brand' => $primary,
                'primary' => $primary,
                'accent' => $accent,
                'indigo' => self::$palettes['indigo'],
                'purple' => self::$palettes['purple'],
                'secondary' => $primary['400'],
                'tertiary' => $primary['700'],
                'semantic' => $semantic,
            ];
        }

        return $out;
    }

    /**
     * Palet client-side dari satu warna hex (untuk preview custom).
     */
    public static function clientPaletteFromHex(?string $hex): ?array
    {
        return self::paletteFromHex($hex);
    }

    /**
     * Bangun palet 50-900 dari satu warna hex (HSL lightening/darkening).
     */
    public static function paletteFromHex(?string $hex): ?array
    {
        $hex = self::normalizeHex($hex);
        if ($hex === null) {
            return null;
        }

        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        // Ramp dibangun relatif terhadap lightness warna asli (500) supaya
        // langkah antar-shade tetap merata dan gradasinya halus, bukan
        // melompat ke lightness absolut yang membuat palet terlihat berat.
        $lightest = 0.97;
        $darkest = 0.06;
        $towardLight = ['50' => 0.94, '100' => 0.80, '200' => 0.60, '300' => 0.35, '400' => 0.15];
        $towardDark = ['600' => 0.14, '700' => 0.26, '800' => 0.42, '900' => 0.55];

        // Saturasi ditahan di shade terang, lalu diredupkan bertahap di shade
        // gelap (pola palet Tailwind) agar tidak terlalu menyala.
        $saturation = [
            '50' => 0.98, '100' => 1.0, '200' => 1.0, '300' => 1.0, '400' => 1.0,
            '500' => 1.0, '600' => 0.97, '700' => 0.90, '800' => 0.82, '900' => 0.74,
        ];

        $out = [];
        foreach ($towardLight as $shade => $ratio) {
            $out[$shade] = self::hslToHex($h, min(1.0, $s * $saturation[$shade]), $l + ($lightest - $l) * $ratio);
        }

        // Shade 500 sedekat mungkin dengan warna asli.
        $out['500'] = $hex;

        foreach ($towardDark as $shade => $ratio) {
            $out[$shade] = self::hslToHex($h, min(1.0, $s * $saturation[$shade]), $l - ($l - $darkest) * $ratio);
        }

        ksort($out, SORT_NUMERIC);

        return $out;
    }

    /**
     * Geser hue sebuah warna (derajat) tanpa mengubah saturasi/lightness.
     */
    public static function shiftHue(string $hex, float $degrees): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        [$h, $s, $l] = self::rgbToHsl($r, $g, $b);

        $h = fmod($h + $degrees / 360, 1.0);
        if ($h < 0) {
            $h += 1.0;
        }

        return self::hslToHex($h, $s, $l);
    }

    /**
     * Normalisasi hex: terima #rgb, #rrggbb, rgb tanpa #.
     */
    public static function normalizeHex(?string $hex): ?string
    {
        if ($hex === null) {
            return null;
        }
        $hex = trim($hex);
        $hex = ltrim($hex, '#');
        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex) === 1) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return null;
        }

        return '#'.strtolower($hex);
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        $h /= 6;

        return [$h, $s, $l];
    }

    public static function hslToHex(float $h, float $s, float $l): string
    {
        if ($s == 0) {
            $v = (int) round($l * 255);

            return sprintf('#%02x%02x%02x', $v, $v, $v);
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = self::hueToRgb($p, $q, $h + 1 / 3);
        $g = self::hueToRgb($p, $q, $h);
        $b = self::hueToRgb($p, $q, $h - 1 / 3);

        return sprintf('#%02x%02x%02x',
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255));
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}

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
     * Palet dasar Tailwind (50-900). Source: tailwindcss.com/docs/colors.
     */
    protected static array $palettes = [
        'rose'    => ['50' => '#fff1f2', '100' => '#ffe4e6', '200' => '#fecdd3', '300' => '#fda4af', '400' => '#fb7185', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '800' => '#9f1239', '900' => '#881337'],
        'blue'    => ['50' => '#eff6ff', '100' => '#dbeafe', '200' => '#bfdbfe', '300' => '#93c5fd', '400' => '#60a5fa', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8', '800' => '#1e40af', '900' => '#1e3a8a'],
        'violet'  => ['50' => '#f5f3ff', '100' => '#ede9fe', '200' => '#ddd6fe', '300' => '#c4b5fd', '400' => '#a78bfa', '500' => '#8b5cf6', '600' => '#7c3aed', '700' => '#6d28d9', '800' => '#5b21b6', '900' => '#4c1d95'],
        'sky'     => ['50' => '#f0f9ff', '100' => '#e0f2fe', '200' => '#bae6fd', '300' => '#7dd3fc', '400' => '#38bdf8', '500' => '#0ea5e9', '600' => '#0284c7', '700' => '#0369a1', '800' => '#075985', '900' => '#0c4a6e'],
        'emerald' => ['50' => '#ecfdf5', '100' => '#d1fae5', '200' => '#a7f3d0', '300' => '#6ee7b7', '400' => '#34d399', '500' => '#10b981', '600' => '#059669', '700' => '#047857', '800' => '#065f46', '900' => '#064e3b'],
        'orange'  => ['50' => '#fff7ed', '100' => '#ffedd5', '200' => '#fed7aa', '300' => '#fdba74', '400' => '#fb923c', '500' => '#f97316', '600' => '#ea580c', '700' => '#c2410c', '800' => '#9a3412', '900' => '#7c2d12'],
        'indigo'  => ['50' => '#eef2ff', '100' => '#e0e7ff', '200' => '#c7d2fe', '300' => '#a5b4fc', '400' => '#818cf8', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '800' => '#3730a3', '900' => '#312e81'],
        'purple'  => ['50' => '#faf5ff', '100' => '#f3e8ff', '200' => '#e9d5ff', '300' => '#d8b4fe', '400' => '#c084fc', '500' => '#a855f7', '600' => '#9333ea', '700' => '#7e22ce', '800' => '#6b21a8', '900' => '#581c87'],
    ];

    /**
     * Definisi preset: [label, primary, accent].
     */
    protected static array $presets = [
        'default' => ['label' => 'Standar (Rose + Indigo)', 'primary' => 'rose', 'accent' => 'indigo'],
        'ocean'   => ['label' => 'Samudra (Sky + Blue)', 'primary' => 'sky', 'accent' => 'blue'],
        'emerald' => ['label' => 'Zamrud (Emerald + Sky)', 'primary' => 'emerald', 'accent' => 'sky'],
        'royal'   => ['label' => 'Kerajaan (Violet + Purple)', 'primary' => 'violet', 'accent' => 'purple'],
        'sunset'  => ['label' => 'Senja (Orange + Rose)', 'primary' => 'orange', 'accent' => 'rose'],
        'rose'    => ['label' => 'Merah Muda (Rose + Violet)', 'primary' => 'rose', 'accent' => 'violet'],
        'custom'  => ['label' => 'Kustom (warna manual)', 'primary' => 'rose', 'accent' => 'indigo'],
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
        if (!isset(self::$presets[$preset])) {
            $preset = self::DEFAULT_PRESET;
        }

        $def = self::$presets[$preset];

        if ($preset === 'custom' && is_string($custom) && $custom !== '') {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                $primary = self::paletteFromHex($decoded['primary'] ?? null);
                $accent  = self::paletteFromHex($decoded['accent'] ?? null);
                if ($primary !== null && $accent !== null) {
                    return [
                        'preset'  => 'custom',
                        'primary' => $primary,
                        'accent'  => $accent,
                    ];
                }
            }
        }

        return [
            'preset'  => $preset,
            'primary' => self::$palettes[$def['primary']],
            'accent'  => self::$palettes[$def['accent']],
        ];
    }

    /**
     * CSS variables (:root) untuk dipakai di layout.
     */
    public static function cssVars(array $theme = null): string
    {
        $theme = $theme ?? self::current();
        $out = [];
        foreach (['primary', 'accent'] as $role) {
            foreach ($theme[$role] as $shade => $hex) {
                $out[] = "--theme-{$role}-{$shade}: {$hex};";
            }
        }
        return implode(' ', $out);
    }

    /**
     * Tag <style> lengkap dengan variabel tema.
     */
    public static function styleTag(array $theme = null): string
    {
        return '<style>:root{' . self::cssVars($theme) . '}</style>';
    }

    /**
     * Tag <style> khusus halaman login (background gradient).
     */
    public static function loginStyleTag(array $theme = null): string
    {
        $theme = $theme ?? self::current();
        $p500 = $theme['primary']['500'] ?? '#f43f5e';
        $a500 = $theme['accent']['500'] ?? '#6366f1';
        $p600 = $theme['primary']['600'] ?? '#e11d48';
        $a600 = $theme['accent']['600'] ?? '#4f46e5';

        return '<style>'
            . ':root{' . self::cssVars($theme) . '}'
            . '.theme-login-bg{background:linear-gradient(135deg,' . $p600 . ' 0%,' . $a600 . ' 100%)}'
            . '.theme-login-btn{background:linear-gradient(135deg,' . $p500 . ',' . $a500 . ')}'
            . '</style>';
    }

    /**
     * Meta theme-color untuk PWA / mobile browser.
     */
    public static function metaColor(string $area = 'primary', array $theme = null): string
    {
        $theme = $theme ?? self::current();
        return $theme[$area]['600'] ?? '#e11d48';
    }

    /**
     * Konfigurasi tema untuk JavaScript (client-side).
     */
    public static function clientConfig(array $theme = null): array
    {
        $theme = $theme ?? self::current();
        return [
            'preset'  => $theme['preset'],
            'primary' => $theme['primary'],
            'accent'  => $theme['accent'],
        ];
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

        $shades = [
            '50'  => 0.97, '100' => 0.94, '200' => 0.88, '300' => 0.78, '400' => 0.65,
            '500' => 0.50, '600' => 0.40, '700' => 0.32, '800' => 0.25, '900' => 0.19,
        ];

        $out = [];
        foreach ($shades as $shade => $targetL) {
            // Pertahankan saturasi dasar, geser lightness ke target.
            $out[$shade] = self::hslToHex($h, $s, $targetL);
        }

        // Shade 500 sedekat mungkin dengan warna asli.
        $out['500'] = $hex;

        return $out;
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
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return null;
        }
        return '#' . strtolower($hex);
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255; $g /= 255; $b /= 255;
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
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }
}

<?php

namespace App\Support;

/**
 * Storefront colours chosen in the admin → CSS variables used by Tailwind (see tailwind.config.js):
 * brand (header/footer/dark buttons), accent (highlights/main buttons), on-accent (text on accent),
 * accent-ink (accent dark enough for text on white), page (page background).
 */
class Theme
{
    public static function cssVariables(): string
    {
        $brand  = self::validHex(Content::get('branding.brand_color'), '#0f172a');
        $accent = self::validHex(Content::get('branding.accent_color'), '#fbbf24');
        $page   = self::validHex(Content::get('branding.page_background'), '#f3f4f6');

        $onAccent = self::contrast($accent, $brand) >= 4.5
            ? $brand
            : (self::contrast($accent, '#ffffff') >= self::contrast($accent, '#000000') ? '#ffffff' : '#000000');

        $vars = [
            '--color-brand'      => self::rgb($brand),
            '--color-accent'     => self::rgb($accent),
            '--color-on-accent'  => self::rgb($onAccent),
            '--color-accent-ink' => self::rgb(self::readableOnWhite($accent)),
            '--color-page'       => self::rgb($page),
        ];

        return ':root{' . collect($vars)->map(fn ($v, $k) => "{$k}: {$v};")->implode('') . '}';
    }

    public static function validHex(?string $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }

    // WCAG contrast ratio between two #rrggbb colours (1–21)
    public static function contrast(string $a, string $b): float
    {
        [$l1, $l2] = [self::luminance($a), self::luminance($b)];

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::components($hex));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    // Darken a colour step by step until it reads well on white (for links/text)
    private static function readableOnWhite(string $hex): string
    {
        $rgb = self::components($hex);

        for ($i = 0; $i < 20 && self::contrast(self::toHex($rgb), '#ffffff') < 4.5; $i++) {
            $rgb = array_map(fn ($c) => (int) round($c * 0.88), $rgb);
        }

        return self::toHex($rgb);
    }

    private static function components(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }

    private static function toHex(array $rgb): string
    {
        return '#' . implode('', array_map(fn ($c) => str_pad(dechex(max(0, min(255, $c))), 2, '0', STR_PAD_LEFT), $rgb));
    }

    private static function rgb(string $hex): string
    {
        return implode(' ', self::components($hex));
    }
}

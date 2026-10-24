<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Small WebP copies of uploaded images (public disk), so 10 MB HD uploads don't slow the storefront.
 *   products/abc.jpg → variants/thumb/products/abc.webp (480 px) and variants/large/products/abc.webp (1280 px)
 * url() falls back to the original when a copy doesn't exist (yet).
 */
class ImageVariants
{
    public const SIZES = [
        'thumb' => 480,
        'large' => 1280,
    ];

    private const QUALITY = 80;

    public static function path(string $original, string $size): string
    {
        $info = pathinfo($original);
        $dir  = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'] . '/';

        return "variants/{$size}/{$dir}{$info['filename']}.webp";
    }

    public static function url(?string $original, string $size): ?string
    {
        if (! $original) {
            return null;
        }

        $disk    = Storage::disk('public');
        $variant = self::path($original, $size);

        return $disk->exists($variant) ? $disk->url($variant) : $disk->url($original);
    }

    // Create all sizes for one uploaded image. Returns false when the file can't be processed.
    public static function generate(string $original, bool $force = false): bool
    {
        $disk = Storage::disk('public');

        if (! function_exists('imagewebp') || ! $disk->exists($original)) {
            return false;
        }

        $pending = array_filter(self::SIZES, fn ($w, $size) => $force || ! $disk->exists(self::path($original, $size)), ARRAY_FILTER_USE_BOTH);

        if (! $pending) {
            return true;
        }

        // Big camera photos need room to decode (e.g. 6000×4000 ≈ 100 MB in memory)
        if (self::bytes(ini_get('memory_limit')) < 512 * 1024 * 1024 && ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '512M');
        }

        $image = @imagecreatefromstring($disk->get($original));

        if (! $image) {
            Log::warning("Image variants: could not read {$original}");

            return false;
        }

        $image = self::applyExifOrientation($image, $disk->path($original));
        imagepalettetotruecolor($image);

        foreach ($pending as $size => $maxWidth) {
            $width  = imagesx($image);
            $scaled = $width > $maxWidth ? imagescale($image, $maxWidth, -1, IMG_BILINEAR_FIXED) : $image;

            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);

            ob_start();
            imagewebp($scaled, null, self::QUALITY);
            $disk->put(self::path($original, $size), ob_get_clean(), 'public');

            if ($scaled !== $image) {
                imagedestroy($scaled);
            }
        }

        imagedestroy($image);

        return true;
    }

    public static function delete(string $original): void
    {
        $disk = Storage::disk('public');

        foreach (array_keys(self::SIZES) as $size) {
            $disk->delete(self::path($original, $size));
        }
    }

    // Phone photos are often stored sideways with an EXIF "rotate me" flag
    private static function applyExifOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? imagerotate($image, $angle, 0) : $image;
    }

    private static function bytes(string|false $value): int
    {
        $value = trim((string) $value);
        $unit  = strtolower(substr($value, -1));
        $num   = (int) $value;

        return match ($unit) {
            'g' => $num * 1024 ** 3,
            'm' => $num * 1024 ** 2,
            'k' => $num * 1024,
            default => $num,
        };
    }
}

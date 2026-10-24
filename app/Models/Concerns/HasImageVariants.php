<?php

namespace App\Models\Concerns;

use App\Support\ImageVariants;
use Illuminate\Support\Facades\Storage;

/**
 * For models with uploaded images: creates the WebP copies after saving, and removes files
 * (original + copies) that were replaced or whose record was deleted, so storage doesn't fill up.
 * The using model lists its image columns in imageAttributes() (string or array-of-paths columns).
 */
trait HasImageVariants
{
    /** @return list<string> */
    abstract protected function imageAttributes(): array;

    public static function bootHasImageVariants(): void
    {
        static::saved(function ($model) {
            $current  = $model->imagePaths();
            $previous = $model->imagePaths(original: true);

            foreach ($current as $path) {
                ImageVariants::generate($path);
            }

            // On create there are no previous paths, so nothing is removed
            foreach (array_diff($previous, $current) as $removed) {
                self::removeImageFile($removed);
            }
        });

        static::deleted(function ($model) {
            foreach ($model->imagePaths() as $path) {
                self::removeImageFile($path);
            }
        });
    }

    /** @return list<string> */
    public function imagePaths(bool $original = false): array
    {
        $paths = [];

        foreach ($this->imageAttributes() as $attribute) {
            $value = $original ? $this->getOriginal($attribute) : $this->getAttribute($attribute);

            if (is_string($value) && str_starts_with($value, '[')) {
                $value = json_decode($value, true);
            }

            foreach ((array) $value as $path) {
                // Only files uploaded through the admin (public disk) – never legacy "storage/…" or external URLs
                if (is_string($path) && $path !== '' && ! str_starts_with($path, 'storage/') && ! preg_match('/^https?:/i', $path)) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private static function removeImageFile(string $path): void
    {
        Storage::disk('public')->delete($path);
        ImageVariants::delete($path);
    }
}

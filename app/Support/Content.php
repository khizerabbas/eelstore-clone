<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Once;

/**
 * Editable storefront content. Keys are "group.name" (e.g. "home.hero_title").
 * Defaults come from config/site_content.php, overrides from the site_contents table (cached).
 * In Blade use the content() helper: {{ content('home.hero_title') }}.
 */
class Content
{
    private const CACHE_KEY = 'site_content.v1';

    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = self::stored();

        if (array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== '') {
            return $stored[$key];
        }

        return config('site_content.' . $key, $default);
    }

    // Text with {year} and {store} placeholders filled in
    public static function text(string $key, array $replace = []): string
    {
        $replace += [
            'year'  => date('Y'),
            'store' => \App\Models\SiteSetting::storeName(),
        ];

        return strtr((string) self::get($key, ''), collect($replace)->mapWithKeys(fn ($v, $k) => ['{' . $k . '}' => $v])->all());
    }

    // Admin-entered link → URL. "/shop" becomes a URL inside this site (works in a subfolder too);
    // http(s) links are kept; anything else (e.g. javascript:) is dropped.
    public static function link(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return url($value);
        }

        return preg_match('/^https?:\/\//i', $value) ? $value : null;
    }

    // Public URL of an uploaded image setting (or null)
    public static function imageUrl(string $key): ?string
    {
        $path = self::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    // All values of one group (defaults merged with stored), keyed without the group prefix
    public static function group(string $group): array
    {
        $values = config('site_content.' . $group, []);

        foreach (self::stored() as $key => $value) {
            if (str_starts_with($key, $group . '.')) {
                $values[substr($key, strlen($group) + 1)] = $value;
            }
        }

        return $values;
    }

    public static function saveGroup(string $group, array $values): void
    {
        foreach ($values as $name => $value) {
            SiteContent::updateOrCreate(['key' => $group . '.' . $name], ['value' => $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }

    // Stored overrides: cached across requests, read once per request
    private static function stored(): array
    {
        return once(function () {
            try {
                return Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('site_contents')
                    ? SiteContent::pluck('value', 'key')->all()
                    : []);
            } catch (\Throwable) {
                return []; // e.g. DB not migrated yet – fall back to defaults
            }
        });
    }

    // Defaults of a key (used by the admin to show "reset" values)
    public static function default(string $key): mixed
    {
        return Arr::get(config('site_content'), $key);
    }
}

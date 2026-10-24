<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Forgiving product search for the storefront.
 * - Every word must match somewhere: name, SKU, brand, category, short or full description.
 * - Names are also compared "compacted" (no spaces, dashes or slashes), so "2.5mm" ↔ "2.5 mm" and "7/29" ↔ "7-29" ↔ "7 29".
 * - Dots are kept, so 2.5 never matches 25.
 * - Best name matches first.
 */
class ProductSearch
{
    public static function apply(Builder $query, string $text): Builder
    {
        $terms = self::terms($text);

        if (! $terms) {
            return $query;
        }

        $compactName = self::compactSql('products.name');
        $compactSku  = self::compactSql("COALESCE(products.sku, '')");

        foreach ($terms as $term) {
            $plain   = '%' . self::escapeLike(mb_strtolower($term)) . '%';
            $compact = '%' . self::escapeLike(self::compact($term)) . '%';

            $query->where(function (Builder $q) use ($plain, $compact, $compactName, $compactSku) {
                $q->whereRaw("LOWER(products.name) LIKE ? ESCAPE '!'", [$plain])
                    ->orWhereRaw("{$compactName} LIKE ? ESCAPE '!'", [$compact])
                    ->orWhereRaw("{$compactSku} LIKE ? ESCAPE '!'", [$compact])
                    ->orWhereRaw("LOWER(COALESCE(products.short_description, '')) LIKE ? ESCAPE '!'", [$plain])
                    ->orWhereRaw("LOWER(COALESCE(products.description, '')) LIKE ? ESCAPE '!'", [$plain])
                    ->orWhereHas('company', fn (Builder $b) => $b->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$plain]))
                    ->orWhereHas('category', fn (Builder $c) => $c->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$plain]));
            });
        }

        // Relevance: whole query in the name > name starts with the first word > anything else
        $whole = '%' . self::escapeLike(self::compact($text)) . '%';
        $first = self::escapeLike(self::compact($terms[0])) . '%';

        return $query
            ->orderByRaw("CASE WHEN {$compactName} LIKE ? ESCAPE '!' THEN 0 WHEN {$compactName} LIKE ? ESCAPE '!' THEN 1 ELSE 2 END", [$whole, $first])
            ->orderBy('products.name');
    }

    /** @return list<string> */
    public static function terms(string $text): array
    {
        $text = mb_strtolower(trim(Str::limit($text, 100, '')));

        $words = preg_split('/[\s,]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // At most 8 words; "2.5mm" vs "2.5 mm" is handled by comparing compacted names, so no splitting is needed
        return array_values(array_unique(array_slice($words, 0, 8)));
    }

    // "7/29 Wire – 2.5 mm" → "7292.5mm"-style comparison form (spaces, dashes, slashes removed; dots kept)
    public static function compact(string $value): string
    {
        return str_replace([' ', '-', '/', '–'], '', mb_strtolower($value));
    }

    private static function compactSql(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(LOWER({$column}), ' ', ''), '-', ''), '/', ''), '–', '')";
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}

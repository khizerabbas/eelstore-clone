<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;

/**
 * schema.org JSON-LD for Google rich results (store details, product prices, breadcrumbs).
 * Output with StructuredData::script($data) – JSON is escaped so it can't break out of the <script> tag.
 */
class StructuredData
{
    public static function script(array $data): string
    {
        $json = json_encode(
            array_filter($data, fn ($v) => $v !== null && $v !== [] && $v !== ''),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    public static function store(): array
    {
        $settings = SiteSetting::current();

        return [
            '@context'  => 'https://schema.org',
            '@type'     => 'Store',
            'name'      => SiteSetting::storeName(),
            'url'       => route('home'),
            'logo'      => Content::imageUrl('branding.logo'),
            'image'     => Content::imageUrl('seo.share_image') ?? Content::imageUrl('branding.logo'),
            'telephone' => $settings->contact_phone,
            'email'     => $settings->contact_email,
            'address'   => $settings->store_address ? [
                '@type'          => 'PostalAddress',
                'streetAddress'  => preg_replace('/\s*\n\s*/', ', ', trim($settings->store_address)),
                'addressCountry' => 'PK',
            ] : null,
            'paymentAccepted' => 'Cash',
            'currenciesAccepted' => 'PKR',
            'sameAs' => array_values(array_filter([
                $settings->facebook_url, $settings->instagram_url, $settings->youtube_url, $settings->x_url,
            ])),
        ];
    }

    public static function product(Product $product): array
    {
        $images = array_values(array_filter($product->imageUrls('large')));

        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product->name,
            'sku'         => $product->sku,
            'url'         => route('product.show', $product->slug),
            'image'       => $images,
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 300) ?: null,
            'brand'       => $product->company ? ['@type' => 'Brand', 'name' => $product->company->name] : null,
            'category'    => $product->category?->name,
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => route('product.show', $product->slug),
                'priceCurrency' => 'PKR',
                'price'         => number_format($product->getDisplayPrice(), 2, '.', ''),
                'availability'  => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
                'seller'        => ['@type' => 'Organization', 'name' => SiteSetting::storeName()],
            ],
        ];
    }

    /** @param array<array{0: string, 1: string}> $crumbs [name, url] pairs */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb[0],
                'item'     => $crumb[1],
            ])->all(),
        ];
    }

    public static function categoryCrumbs(Category $category): array
    {
        $crumbs = [[content('header.nav_home'), route('home')], [Content::text('pages.shop_title'), route('shop.index')]];

        if ($category->parent) {
            $crumbs[] = [$category->parent->name, route('category.show', $category->parent->slug)];
        }

        $crumbs[] = [$category->name, route('category.show', $category->slug)];

        return $crumbs;
    }
}

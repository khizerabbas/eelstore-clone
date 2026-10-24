<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    // Private/utility pages stay out of search results; non-production sites are hidden completely
    public function robots()
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (['/admin', '/cart', '/checkout', '/my-orders', '/orders/', '/wishlist', '/search'] as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    // All public pages Google should know about (cached for an hour)
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('shop.index'), 'priority' => '0.9'],
                ['loc' => route('contact.show'), 'priority' => '0.5'],
            ]);

            Category::where('is_active', true)->orderBy('sort_order')->get(['slug', 'updated_at'])
                ->each(fn ($c) => $urls->push(['loc' => route('category.show', $c->slug), 'lastmod' => $c->updated_at, 'priority' => '0.8']));

            Company::where('is_active', true)->whereHas('products', fn ($q) => $q->where('is_active', true))->get(['slug', 'updated_at'])
                ->each(fn ($b) => $urls->push(['loc' => route('brand.show', $b->slug), 'lastmod' => $b->updated_at, 'priority' => '0.6']));

            Product::where('is_active', true)->latest('updated_at')->get(['slug', 'updated_at'])
                ->each(fn ($p) => $urls->push(['loc' => route('product.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.7']));

            Page::where('is_published', true)->get(['slug', 'updated_at'])
                ->each(fn ($p) => $urls->push(['loc' => route('pages.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.4']));

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

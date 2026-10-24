<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): array
    {
        $parent = Category::create(['name' => 'Lighting', 'slug' => 'lighting', 'description' => 'Bulbs and lights', 'meta_title' => 'LED Lights in Lahore']);
        $child  = Category::create(['name' => 'LED Bulbs', 'slug' => 'led-bulbs', 'parent_id' => $parent->id]);
        $other  = Category::create(['name' => 'Wires', 'slug' => 'wires']);
        $brand  = Company::create(['name' => 'Philips', 'slug' => 'philips']);

        $bulb = Product::create(['category_id' => $child->id, 'company_id' => $brand->id, 'name' => '12W LED Bulb', 'slug' => '12w-led-bulb', 'price' => 450, 'stock' => 10, 'sku' => 'LED-12']);
        $wire = Product::create(['category_id' => $other->id, 'name' => 'Copper Wire', 'slug' => 'copper-wire', 'price' => 2500]);
        $hidden = Product::create(['category_id' => $other->id, 'name' => 'Hidden Wire', 'slug' => 'hidden-wire', 'price' => 10, 'is_active' => false]);

        return compact('parent', 'child', 'other', 'bulb', 'wire', 'hidden');
    }

    public function test_category_page_is_a_real_page_with_its_subcategories(): void
    {
        $this->catalog();

        $this->get('/category/lighting')
            ->assertOk()
            ->assertSee('<title>LED Lights in Lahore – Usman Electronics</title>', false)
            ->assertSee('Bulbs and lights')
            ->assertSee('12W LED Bulb')       // from the sub-category
            ->assertDontSee('Copper Wire')
            ->assertDontSee('noindex', false)
            ->assertSee('"@type":"BreadcrumbList"', false);

        // Sorted/filtered variants are not indexed
        $this->get('/category/lighting?sort=latest')->assertSee('noindex', false);
        $this->get('/category/does-not-exist')->assertNotFound();
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $this->catalog();
        Page::create(['title' => 'About', 'slug' => 'about']);
        Page::create(['title' => 'Draft', 'slug' => 'draft', 'is_published' => false]);
        Cache::forget('sitemap.xml');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('product.show', '12w-led-bulb'))
            ->assertSee(route('category.show', 'lighting'))
            ->assertSee(route('pages.show', 'about'))
            ->assertDontSee('hidden-wire')
            ->assertDontSee('/pages/draft');
    }

    public function test_robots_hides_private_pages_in_production_and_everything_elsewhere(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        $this->app['env'] = 'production';

        $this->get('/robots.txt')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /checkout')
            ->assertSee('Sitemap: ' . route('sitemap'));
    }

    public function test_product_page_has_structured_data(): void
    {
        $this->catalog();

        $response = $this->get('/product/12w-led-bulb')->assertOk();

        $response->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"PKR"', false)
            ->assertSee('"price":"450.00"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSee('"brand":{"@type":"Brand","name":"Philips"}', false);
    }

    public function test_structured_data_cannot_break_out_of_the_script_tag(): void
    {
        $category = Category::create(['name' => 'X', 'slug' => 'x']);
        Product::create(['category_id' => $category->id, 'name' => '</script><script>alert(1)</script>', 'slug' => 'evil', 'price' => 1]);

        $this->get('/product/evil')->assertOk()->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_home_has_store_structured_data(): void
    {
        $this->get('/')->assertSee('"@type":"Store"', false)->assertSee('"currenciesAccepted":"PKR"', false);
    }

    public function test_private_pages_are_noindex(): void
    {
        foreach (['/cart', '/wishlist', '/search?q=x', '/my-orders'] as $url) {
            $this->get($url)->assertSee('noindex', false);
        }
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');

        // Order pages keep their stricter policy
        $order = \App\Models\Order::create(['customer_name' => 'A', 'customer_phone' => '+923001234567']);
        $this->get(route('orders.show', $order))->assertHeader('Referrer-Policy', 'no-referrer');

        // Admin is protected too
        $this->get('/admin/login')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_cart_posts_are_rate_limited(): void
    {
        ['wire' => $wire] = $this->catalog();

        for ($i = 0; $i < 60; $i++) {
            $this->post(route('cart.add', $wire));
        }

        $this->post(route('cart.add', $wire))->assertStatus(429);
    }
}

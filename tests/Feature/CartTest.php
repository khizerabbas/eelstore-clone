<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomepageCategorySection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'wires'], ['name' => 'Wires']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name'        => 'Test Wire',
            'slug'        => 'test-wire-' . uniqid(),
            'price'       => 1000,
            'sale_price'  => null,
            'is_on_sale'  => false,
            'stock'       => 10,
            'is_active'   => true,
        ], $attributes));
    }

    public function test_updating_quantity_keeps_cart_line_valid(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('cart.update', $product), ['quantity' => 3]);

        $this->assertSame(3, session('cart')[$product->id]['quantity']);

        $response = $this->get(route('cart.index'));
        $response->assertOk();

        $line = $response->viewData('products')->first();
        $this->assertSame(3, $line->cart_quantity);
        $this->assertEquals(3000, $response->viewData('total'));
    }

    public function test_update_to_zero_removes_line(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.add', $product), ['quantity' => 2]);
        $this->post(route('cart.update', $product), ['quantity' => 0]);

        $this->assertArrayNotHasKey($product->id, session('cart'));
    }

    public function test_cart_ignores_sale_price_when_product_not_on_sale(): void
    {
        $notOnSale = $this->makeProduct(['sale_price' => 500, 'is_on_sale' => false]);
        $onSale    = $this->makeProduct(['sale_price' => 800, 'is_on_sale' => true]);

        $this->post(route('cart.add', $notOnSale), ['quantity' => 1]);
        $this->post(route('cart.add', $onSale), ['quantity' => 1]);

        $response = $this->get(route('cart.index'));

        // 1000 (sale price ignored) + 800 (sale price applied)
        $this->assertEquals(1800, $response->viewData('total'));
    }

    public function test_shop_price_filter_uses_same_price_rule(): void
    {
        $this->makeProduct(['name' => 'Not On Sale', 'sale_price' => 500, 'is_on_sale' => false]);
        $this->makeProduct(['name' => 'On Sale', 'price' => 2000, 'sale_price' => 600, 'is_on_sale' => true]);

        $response = $this->get(route('shop.index', ['min_price' => 0, 'max_price' => 700]));

        $names = $response->viewData('products')->pluck('name')->all();
        $this->assertSame(['On Sale'], $names);
    }

    public function test_fresh_seed_creates_homepage_sections(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, HomepageCategorySection::count());
    }
}

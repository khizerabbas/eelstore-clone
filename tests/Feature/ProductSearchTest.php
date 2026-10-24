<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $wires  = Category::create(['name' => 'Wires', 'slug' => 'wires']);
        $lights = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        $fast   = Company::create(['name' => 'Fast Cables', 'slug' => 'fast-cables']);

        $make = fn (array $a) => Product::create($a + ['price' => 100, 'is_active' => true]);

        $make(['name' => '2.5 mm Single Core Wire', 'slug' => 'a', 'category_id' => $wires->id, 'company_id' => $fast->id, 'sku' => 'FC-25']);
        $make(['name' => '4mm Single Core Wire', 'slug' => 'b', 'category_id' => $wires->id, 'company_id' => $fast->id]);
        $make(['name' => '7/29 Wire 90m', 'slug' => 'c', 'category_id' => $wires->id]);
        $make(['name' => '25 mm Conduit Pipe', 'slug' => 'd', 'category_id' => $wires->id]);
        $make(['name' => '12W LED Bulb', 'slug' => 'e', 'category_id' => $lights->id, 'short_description' => 'Cool white daylight']);
        $make(['name' => '2.5mm Hidden Wire', 'slug' => 'f', 'category_id' => $wires->id, 'is_active' => false]);
    }

    private function search(string $q): array
    {
        return $this->get('/search?q=' . urlencode($q))->viewData('products')->pluck('name')->all();
    }

    public function test_spacing_between_number_and_unit_does_not_matter(): void
    {
        $this->assertSame(['2.5 mm Single Core Wire'], $this->search('2.5mm'));
        $this->assertSame(['2.5 mm Single Core Wire'], $this->search('2.5 mm'));
        $this->assertSame(['4mm Single Core Wire'], $this->search('4 mm'));
        $this->assertSame(['12W LED Bulb'], $this->search('12 w bulb'));
    }

    public function test_dots_are_respected(): void
    {
        $this->assertNotContains('25 mm Conduit Pipe', $this->search('2.5mm'));
        $this->assertNotContains('2.5 mm Single Core Wire', $this->search('25mm'));
    }

    public function test_slashes_and_dashes_are_interchangeable(): void
    {
        foreach (['7/29', '7-29', '7 29 wire'] as $q) {
            $this->assertSame(['7/29 Wire 90m'], $this->search($q), "query: {$q}");
        }
    }

    public function test_brand_category_sku_and_description_are_searched(): void
    {
        $this->assertEqualsCanonicalizing(['2.5 mm Single Core Wire', '4mm Single Core Wire'], $this->search('fast cables'));
        $this->assertContains('12W LED Bulb', $this->search('lighting'));
        $this->assertSame(['2.5 mm Single Core Wire'], $this->search('fc-25'));
        $this->assertSame(['12W LED Bulb'], $this->search('daylight'));
    }

    public function test_every_word_must_match_in_any_order(): void
    {
        $this->assertSame(['2.5 mm Single Core Wire'], $this->search('wire fast 2.5'));
        $this->assertSame([], $this->search('bulb fast'));
    }

    public function test_best_name_matches_come_first(): void
    {
        $results = $this->search('single core wire');

        $this->assertSame(['2.5 mm Single Core Wire', '4mm Single Core Wire'], $results);
        // A product whose name starts with the searched words ranks above ones that only contain them
        $this->assertSame('7/29 Wire 90m', $this->search('7/29 wire')[0]);
        $this->assertSame('2.5 mm Single Core Wire', $this->search('2.5mm wire')[0]);
    }

    public function test_hidden_products_and_like_wildcards(): void
    {
        $this->assertNotContains('2.5mm Hidden Wire', $this->search('hidden'));
        $this->assertSame([], $this->search('%'));
        $this->assertSame([], $this->search('_'));
    }
}

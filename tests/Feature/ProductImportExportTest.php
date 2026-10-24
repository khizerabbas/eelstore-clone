<?php

namespace Tests\Feature;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->owner()->create());
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'name'      => '7/29 Wire 90m',
            'sku'       => 'FC-729-90',
            'category'  => 'Wires',
            'brand'     => 'Fast Cables',
            'price'     => '9000',
            'stock'     => '25',
            'size_group' => '7/29 wire',
            'size_label' => '90 m',
        ], $overrides);
    }

    public function test_new_product_is_created_with_category_and_brand_by_name(): void
    {
        ProductImporter::test()->import($this->row())->assertImported();

        $product = Product::sole();
        $this->assertSame('7/29 Wire 90m', $product->name);
        $this->assertSame('7-29-wire-90m', $product->slug);
        $this->assertSame('Wires', $product->category->name);
        $this->assertSame('Fast Cables', $product->company->name);
        $this->assertEquals(9000, $product->price);
        $this->assertTrue($product->is_active);   // default for new products
        $this->assertFalse($product->is_on_sale);
        $this->assertSame('7/29 wire', $product->size_group);

        // Existing names are reused, not duplicated
        ProductImporter::test()->import($this->row(['name' => '7/29 Wire 180m', 'sku' => 'FC-729-180', 'price' => '17500']))->assertImported();
        $this->assertSame(1, Category::count());
        $this->assertSame(1, Company::count());
    }

    public function test_reimporting_updates_by_sku_instead_of_duplicating(): void
    {
        ProductImporter::test()->import($this->row())->assertImported();

        ProductImporter::test()->import(['sku' => 'FC-729-90', 'price' => '9500', 'sale_price' => '9200'])->assertImported();

        $product = Product::sole();
        $this->assertEquals(9500, $product->price);
        $this->assertEquals(9200, $product->getDisplayPrice()); // sale price → on sale automatically
        $this->assertSame('Wires', $product->category->name); // untouched columns stay
    }

    public function test_rows_are_matched_by_name_when_there_is_no_sku(): void
    {
        ProductImporter::test()->import($this->row(['sku' => '']))->assertImported();
        ProductImporter::test()->import(['name' => '7/29 Wire 90m', 'stock' => '3'])->assertImported();

        $this->assertSame(3, Product::sole()->stock);
    }

    public function test_new_products_need_name_category_and_price(): void
    {
        ProductImporter::test()->import($this->row(['price' => '']))->assertHasRowFailure('Price is required for a new product.');
        ProductImporter::test()->import($this->row(['category' => '']))->assertHasRowFailure('Category is required for a new product.');

        $this->assertSame(0, Product::count());
    }

    public function test_invalid_values_are_rejected(): void
    {
        ProductImporter::test()->import($this->row(['sale_price' => '9500', 'is_on_sale' => 'yes']))
            ->assertHasRowFailure('Sale price must be lower than the price.');

        ProductImporter::test()->import($this->row(['price' => '-5']));
        ProductImporter::test()->import($this->row(['slug' => 'Not A Slug']));

        $this->assertSame(0, Product::count());
    }

    public function test_slugs_stay_unique(): void
    {
        ProductImporter::test()->import($this->row(['sku' => 'A']))->assertImported();
        ProductImporter::test()->import($this->row(['sku' => 'B', 'name' => '7/29 Wire 90m ']))->assertImported();

        // Second row matched the first by name (trimmed) – no duplicate created
        $this->assertSame(1, Product::count());

        ProductImporter::test()->import($this->row(['sku' => 'C', 'name' => '7/29 Wire 90m!']))->assertImported();
        $this->assertSame(['7-29-wire-90m', '7-29-wire-90m-2'], Product::orderBy('id')->pluck('slug')->all());
    }

    public function test_export_columns_match_the_import(): void
    {
        $exportLabels = collect(ProductExporter::getColumns())->map->getLabel()->all();
        $importLabels = collect(ProductImporter::getColumns())->map->getLabel()->all();

        foreach (['Name', 'SKU', 'Category', 'Brand', 'Price (Rs)', 'Sale price (Rs)', 'Stock', 'Size group'] as $label) {
            $this->assertContains($label, $exportLabels);
            $this->assertContains($label, $importLabels);
        }
    }

    public function test_products_page_has_import_and_export_buttons(): void
    {
        $this->get(ProductResource::getUrl('index'))->assertOk()->assertSee('Import')->assertSee('Export');
    }
}

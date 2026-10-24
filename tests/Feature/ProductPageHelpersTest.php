<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Content;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPageHelpersTest extends TestCase
{
    use RefreshDatabase;

    private function wires(): array
    {
        $category = Category::create(['name' => 'Wires', 'slug' => 'wires']);
        $make = fn (array $a) => Product::create($a + ['category_id' => $category->id, 'is_active' => true]);

        return [
            'coil90'  => $make(['name' => '7/29 Wire 90m', 'slug' => 'wire-90', 'price' => 9000, 'size_group' => '7/29 wire', 'size_label' => '90 m']),
            'coil180' => $make(['name' => '7/29 Wire 180m', 'slug' => 'wire-180', 'price' => 17500, 'size_group' => '7/29 wire', 'size_label' => '180 m']),
            'hidden'  => $make(['name' => '7/29 Wire 45m', 'slug' => 'wire-45', 'price' => 4600, 'size_group' => '7/29 wire', 'size_label' => '45 m', 'is_active' => false]),
            // different group (own category, so it can't show up under "Related products" either)
            'other'   => Product::create(['category_id' => Category::create(['name' => 'Cables', 'slug' => 'cables'])->id, 'name' => '3/29 Wire 90m', 'slug' => 'wire-3-90', 'price' => 5000, 'size_group' => '3/29 wire']),
            'single'  => $make(['name' => 'Socket', 'slug' => 'socket', 'price' => 300]),
        ];
    }

    public function test_other_sizes_link_products_in_the_same_group(): void
    {
        $this->wires();

        $this->get('/product/wire-90')
            ->assertOk()
            ->assertSee('Other sizes')
            ->assertSeeInOrder(['90 m', 'Rs 9,000', '180 m', 'Rs 17,500'])
            ->assertSee(route('product.show', 'wire-180'))
            ->assertDontSee(route('product.show', 'wire-45'))    // hidden product
            ->assertDontSee(route('product.show', 'wire-3-90')); // different group

        $this->get('/product/socket')->assertOk()->assertDontSee('Other sizes');
    }

    public function test_customer_note_is_shown_only_when_set(): void
    {
        ['coil90' => $coil] = $this->wires();
        $coil->update(['customer_note' => 'Custom lengths available – mention it in the order comment.']);

        $this->get('/product/wire-90')->assertSee('Custom lengths available – mention it in the order comment.');
        $this->get('/product/wire-180')->assertDontSee('Custom lengths available');
    }

    public function test_whatsapp_button_prefills_the_product(): void
    {
        $this->wires();

        $this->get('/product/wire-90')
            ->assertSee('Ask about this product on WhatsApp')
            ->assertSee('https://wa.me/923234146388?text=' . rawurlencode('Assalam o Alaikum, I have a question about: 7/29 Wire 90m – ' . route('product.show', 'wire-90')), false);
    }

    public function test_texts_are_editable(): void
    {
        $this->wires();
        Content::saveGroup('pages', ['product_other_sizes' => 'Coil sizes', 'product_whatsapp_button' => 'WhatsApp us']);

        $this->get('/product/wire-90')->assertSee('Coil sizes')->assertSee('WhatsApp us');
    }

    public function test_admin_can_set_size_group_and_note(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $category = Category::create(['name' => 'Wires', 'slug' => 'wires']);
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name'          => '7/29 Wire 90m',
                'slug'          => '7-29-wire-90m',
                'category_id'   => $category->id,
                'price'         => 9000,
                'size_group'    => '7/29 wire',
                'size_label'    => '90 m',
                'customer_note' => 'Custom lengths available.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::sole();
        $this->assertSame('7/29 wire', $product->size_group);
        $this->assertSame('90 m', $product->sizeLabel());
        $this->assertSame('Custom lengths available.', $product->customer_note);
    }
}

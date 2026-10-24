<?php

namespace Tests\Feature;

use App\Filament\Pages\Website\SeoSettings;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Content;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Wires', 'slug' => 'wires']);

        return Product::create(['category_id' => $category->id, 'name' => 'Copper Wire', 'slug' => 'copper', 'price' => 2500]);
    }

    public function test_nothing_is_loaded_without_ids(): void
    {
        $this->get('/')
            ->assertSee('window.ueTrack = function', false)   // safe no-op helper
            ->assertDontSee('googletagmanager.com/gtag', false)
            ->assertDontSee('fbevents.js', false);
    }

    public function test_scripts_load_when_ids_are_set_but_never_in_the_admin(): void
    {
        Content::saveGroup('seo', ['ga4_id' => 'G-ABC123XYZ', 'meta_pixel_id' => '1234567890123']);

        $this->get('/')
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
            ->assertSee("fbq('init', '1234567890123')", false);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->owner()->create());
        $this->get('/admin')->assertDontSee('googletagmanager', false);
    }

    public function test_invalid_ids_are_rejected_in_the_admin(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(SeoSettings::class)
            ->fillForm(['seo.ga4_id' => 'UA-12345', 'seo.meta_pixel_id' => '<script>'])
            ->call('save')
            ->assertHasFormErrors(['seo.ga4_id', 'seo.meta_pixel_id']);
    }

    public function test_add_to_cart_returns_product_for_tracking(): void
    {
        $product = $this->product();

        $this->postJson(route('cart.add', $product), ['quantity' => 2])
            ->assertJsonPath('product.name', 'Copper Wire')
            ->assertJsonPath('product.quantity', 2);
    }

    public function test_checkout_and_order_events(): void
    {
        Mail::fake();
        Bus::fake();
        $product = $this->product();
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $this->get(route('checkout.show'))->assertSee("window.ueTrack('begin_checkout'", false);

        $this->post(route('checkout.store'), ['name' => 'Ali', 'phone' => '03001234567']);
        $order = Order::sole();

        // Lead event only right after submitting – not when the customer reopens the page later
        $this->get(route('orders.show', $order))->assertSee("window.ueTrack('lead'", false)->assertSee('order_request', false);
        $this->get(route('orders.show', $order))->assertDontSee("window.ueTrack('lead'", false);
    }
}

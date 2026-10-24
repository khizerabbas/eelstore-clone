<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\HomepageCategorySections\HomepageCategorySectionResource;
use App\Filament\Resources\HomepageSliders\HomepageSliderResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function makeOrder(array $attributes = []): Order
    {
        $category = Category::firstOrCreate(['slug' => 'wires'], ['name' => 'Wires']);
        $product  = Product::create(['category_id' => $category->id, 'name' => 'Copper Wire', 'slug' => 'copper-wire-' . uniqid(), 'price' => 1000]);

        $order = Order::create(array_merge([
            'customer_name'  => 'Ali Khan',
            'customer_phone' => '+923001234567',
            'items_count'    => 2,
            'subtotal'       => 2000,
        ], $attributes));

        $order->items()->create([
            'product_id'   => $product->id,
            'product_name' => 'Copper Wire',
            'unit_price'   => 1000,
            'quantity'     => 2,
            'line_total'   => 2000,
        ]);

        return $order;
    }

    public function test_guests_are_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get(route('admin.orders.print', 1))->assertRedirect('/admin/login');
    }

    public function test_public_registration_and_customer_auth_routes_are_gone(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/login')->assertNotFound();
        $this->get('/dashboard')->assertNotFound();
        $this->get('/profile')->assertNotFound();
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $this->actingAs(User::factory()->owner()->create(['is_active' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_owner_can_open_every_admin_screen(): void
    {
        $order = $this->makeOrder();
        $this->actingAs(User::factory()->owner()->create());

        $urls = [
            '/admin',
            OrderResource::getUrl('index'),
            OrderResource::getUrl('view', ['record' => $order]),
            OrderResource::getUrl('edit', ['record' => $order]),
            ProductResource::getUrl('index'),
            ProductResource::getUrl('create'),
            ProductResource::getUrl('edit', ['record' => $order->items->first()->product_id]),
            CategoryResource::getUrl('index'),
            CompanyResource::getUrl('index'),
            HomepageSliderResource::getUrl('index'),
            HomepageCategorySectionResource::getUrl('index'),
            PageResource::getUrl('index'),
            PageResource::getUrl('create'),
            ContactMessageResource::getUrl('index'),
            UserResource::getUrl('index'),
            SiteSettings::getUrl(),
            route('admin.orders.print', $order->id),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_order_urls_use_id_not_the_secret_token(): void
    {
        $order = $this->makeOrder();

        $url = OrderResource::getUrl('view', ['record' => $order]);

        $this->assertStringEndsWith('/admin/orders/' . $order->id, $url);
        $this->assertStringNotContainsString($order->public_token, $url);
    }

    public function test_staff_cannot_open_settings_or_admin_users_or_delete(): void
    {
        $order = $this->makeOrder();
        $staff = User::factory()->create();
        $this->actingAs($staff);

        $this->get(OrderResource::getUrl('index'))->assertOk();
        $this->get(ProductResource::getUrl('index'))->assertOk();
        $this->get(SiteSettings::getUrl())->assertForbidden();
        $this->get(UserResource::getUrl('index'))->assertForbidden();

        $this->assertFalse($staff->can('delete', $order));
        $this->assertFalse($staff->can('create', Order::class));
    }

    public function test_mark_contacted_and_change_status_actions(): void
    {
        $order = $this->makeOrder();
        $this->actingAs(User::factory()->owner()->create(['name' => 'Usman']));

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('markContacted');

        $order->refresh();
        $this->assertSame(OrderStatus::Contacted, $order->status);
        $this->assertNotNull($order->contacted_at);
        $this->assertStringContainsString('Contacted by Usman', $order->admin_note);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', data: ['status' => OrderStatus::Confirmed->value, 'note' => 'Deliver Friday']);

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertStringContainsString('Deliver Friday', $order->admin_note);

        // The customer's status page reflects it
        $this->get(route('orders.show', $order))->assertSee(OrderStatus::Confirmed->customerMessage());
    }

    public function test_order_list_tabs_filter_by_status(): void
    {
        $new       = $this->makeOrder(['customer_name' => 'New Customer']);
        $delivered = $this->makeOrder(['customer_name' => 'Delivered Customer']);
        $delivered->markStatus(OrderStatus::Delivered);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$new, $delivered])
            ->set('activeTab', 'new')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$delivered]);
    }

    public function test_editing_order_normalises_phone(): void
    {
        $order = $this->makeOrder();
        $this->actingAs(User::factory()->create());

        Livewire::test(EditOrder::class, ['record' => $order->id])
            ->fillForm(['customer_phone' => '0321 7654321'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('+923217654321', $order->fresh()->customer_phone);

        Livewire::test(EditOrder::class, ['record' => $order->id])
            ->fillForm(['customer_phone' => '12345'])
            ->call('save')
            ->assertHasFormErrors(['customer_phone']);
    }

    public function test_staff_can_create_a_product(): void
    {
        $category = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name'        => 'LED Bulb 12W',
                'slug'        => 'led-bulb-12w',
                'category_id' => $category->id,
                'price'       => 450,
                'is_on_sale'  => true,
                'sale_price'  => 399,
                'stock'       => 20,
                'is_active'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'led-bulb-12w')->sole();
        $this->assertEquals(399, $product->getDisplayPrice());
    }

    public function test_sale_price_must_be_below_regular_price(): void
    {
        $category = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name'        => 'LED Bulb',
                'slug'        => 'led-bulb',
                'category_id' => $category->id,
                'price'       => 450,
                'is_on_sale'  => true,
                'sale_price'  => 500,
            ])
            ->call('create')
            ->assertHasFormErrors(['sale_price']);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $order = $this->makeOrder();
        $owner = User::factory()->owner()->create();
        $empty = Category::create(['name' => 'Empty', 'slug' => 'empty']);

        $this->assertFalse($owner->can('delete', $order->items->first()->product->category));
        $this->assertTrue($owner->can('delete', $empty));
    }

    public function test_settings_save_and_reject_unsafe_links(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(SiteSettings::class)
            ->fillForm(['facebook_url' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['facebook_url']);

        Livewire::test(SiteSettings::class)
            ->fillForm(['map_embed_url' => 'https://evil.example/frame'])
            ->call('save')
            ->assertHasFormErrors(['map_embed_url']);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'store_name'    => 'Usman Electronics',
                'facebook_url'  => 'https://facebook.com/usman',
                'map_embed_url' => '<iframe src="https://www.google.com/maps/embed?pb=abc" width="600"></iframe>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();
        $this->assertSame('https://facebook.com/usman', $settings->facebook_url);
        $this->assertSame('https://www.google.com/maps/embed?pb=abc', $settings->map_embed_url);
    }

    public function test_owner_cannot_delete_themselves(): void
    {
        $owner = User::factory()->owner()->create();
        $other = User::factory()->create();

        $this->assertFalse($owner->can('delete', $owner));
        $this->assertTrue($owner->can('delete', $other));
    }

    public function test_create_admin_command(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password (min 12 characters, letters, numbers and symbols)', 'Very-Strong-Pass-123')
            ->expectsQuestion('Role', 'owner')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'owner@example.com')->sole()->isOwner());
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Widgets\CashCollectedChart;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrderStats;
use App\Filament\Widgets\RequestsPerWeekChart;
use App\Models\AdminActivity;
use App\Models\Category;
use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\ErrorLog;
use App\Models\HomepageCategorySection;
use App\Models\HomepageSlider;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Opens every admin screen with realistic data (the demo seed + one of each record type),
 * as the owner and as a staff member, so a broken page or a wrong permission shows up here.
 */
class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private QuoteRequest $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $product = Product::firstOrFail();

        $this->order = Order::create([
            'customer_name'    => 'Ali Khan',
            'customer_phone'   => '+923001234567',
            'customer_email'   => 'ali@example.com',
            'customer_address' => 'Shop 4, Hall Road, Lahore',
            'customer_comment' => 'Please call after 5pm',
            'items_count'      => 2,
            'subtotal'         => 2 * $product->getDisplayPrice(),
        ]);
        $this->order->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'unit_price'   => $product->getDisplayPrice(),
            'quantity'     => 2,
            'line_total'   => 2 * $product->getDisplayPrice(),
        ]);

        $this->quote = QuoteRequest::create([
            'name'       => 'Bilal Traders',
            'phone'      => '+923211234567',
            'items'      => "200m 2.5mm wire\n50 switches",
            'cart_items' => [['name' => $product->name, 'quantity' => 3, 'price' => 100]],
        ]);

        ContactMessage::create(['name' => 'Sara', 'phone' => '03001234567', 'message' => 'Do you deliver to Multan?']);
        ErrorLog::create(['fingerprint' => sha1('x'), 'source' => 'server', 'level' => 'error', 'type' => 'RuntimeException', 'message' => 'Test failure']);
        AdminActivity::create(['user_name' => 'Owner', 'action' => 'updated', 'subject_type' => Product::class, 'subject_id' => $product->id, 'subject_label' => $product->name]);
        Page::create(['title' => 'Delivery policy', 'slug' => 'delivery-policy', 'body' => '<p>We deliver.</p>', 'is_published' => true]);
        HomepageSlider::create(['title' => 'Big sale', 'image_path' => 'hero/sale.jpg', 'is_active' => true]);
    }

    /** @return array<string, string> label => URL, readable by every admin */
    private function sharedScreens(): array
    {
        $product = Product::firstOrFail();

        return [
            'dashboard'          => '/admin',
            'orders'             => '/admin/orders',
            'order view'         => '/admin/orders/' . $this->order->id,
            'order edit'         => '/admin/orders/' . $this->order->id . '/edit',
            'order print'        => '/admin/orders/' . $this->order->id . '/print',
            'quotes'             => '/admin/quote-requests',
            'quote view'         => '/admin/quote-requests/' . $this->quote->id,
            'quote edit'         => '/admin/quote-requests/' . $this->quote->id . '/edit',
            'products'           => '/admin/products',
            'product create'     => '/admin/products/create',
            'product edit'       => '/admin/products/' . $product->id . '/edit',
            'categories'         => '/admin/categories',
            'category create'    => '/admin/categories/create',
            'category edit'      => '/admin/categories/' . Category::firstOrFail()->id . '/edit',
            'brands'             => '/admin/companies',
            'contact messages'   => '/admin/contact-messages',
            'homepage sliders'   => '/admin/homepage-sliders',
            'homepage sections'  => '/admin/homepage-category-sections',
            'pages'              => '/admin/pages',
            'page create'        => '/admin/pages/create',
            'page edit'          => '/admin/pages/' . Page::firstOrFail()->id . '/edit',
            'profile'            => '/admin/profile',
        ];
    }

    /** @return array<string, string> owner-only screens */
    private function ownerScreens(): array
    {
        return [
            'site settings'  => '/admin/site-settings',
            'branding'       => '/admin/branding-theme',
            'header & menu'  => '/admin/header-menu',
            'home page'      => '/admin/home-page',
            'footer'         => '/admin/footer-content',
            'page texts'     => '/admin/page-texts',
            'seo'            => '/admin/seo-settings',
            'backups'        => '/admin/backups',
            'users'          => '/admin/users',
            'user create'    => '/admin/users/create',
            'user edit'      => '/admin/users/' . User::firstOrFail()->id . '/edit',
            'error log'      => '/admin/error-logs',
            'activity log'   => '/admin/admin-activities',
        ];
    }

    public function test_owner_can_open_every_admin_screen(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        foreach ($this->sharedScreens() + $this->ownerScreens() as $label => $url) {
            $response = $this->get($url);

            $this->assertSame(200, $response->getStatusCode(), "Owner could not open {$label} ({$url})");
        }
    }

    public function test_staff_can_work_with_orders_and_catalog_but_not_settings(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->sharedScreens() as $label => $url) {
            $this->assertSame(200, $this->get($url)->getStatusCode(), "Staff could not open {$label} ({$url})");
        }

        foreach ($this->ownerScreens() as $label => $url) {
            $this->assertSame(403, $this->get($url)->getStatusCode(), "Staff should not open {$label} ({$url})");
        }
    }

    public function test_quotes_and_orders_cannot_be_created_in_the_admin(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->get('/admin/orders/create')->assertNotFound();
        $this->get('/admin/quote-requests/create')->assertNotFound();
    }

    public function test_dashboard_shows_the_new_order(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        // Dashboard widgets load lazily, so render them directly
        Livewire::test(LatestOrders::class)->assertSee('Ali Khan');
        Livewire::test(OrderStats::class)->assertOk();
        Livewire::test(RequestsPerWeekChart::class)->assertOk();
        Livewire::test(CashCollectedChart::class)->assertOk();
    }

    public function test_brand_and_section_screens_list_seeded_records(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->get('/admin/companies')->assertOk()->assertSee(Company::firstOrFail()->name);
        $this->get('/admin/homepage-category-sections')->assertOk()
            ->assertSee(HomepageCategorySection::with('category')->firstOrFail()->category->name);
    }
}

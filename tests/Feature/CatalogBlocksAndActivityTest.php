<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\Website\HomePage;
use App\Filament\Resources\AdminActivities\AdminActivityResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\AdminActivity;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Content;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogBlocksAndActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function catalog(): array
    {
        $category = Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        $philips  = Company::create(['name' => 'Philips', 'slug' => 'philips', 'description' => 'Dutch lighting brand']);
        $osram    = Company::create(['name' => 'Osram', 'slug' => 'osram']);

        $deal     = Product::create(['category_id' => $category->id, 'company_id' => $philips->id, 'name' => 'Deal Bulb', 'slug' => 'deal-bulb', 'price' => 500, 'sale_price' => 400, 'is_on_sale' => true]);
        $featured = Product::create(['category_id' => $category->id, 'company_id' => $osram->id, 'name' => 'Featured Tube', 'slug' => 'featured-tube', 'price' => 900, 'is_featured' => true]);
        $plain    = Product::create(['category_id' => $category->id, 'company_id' => $osram->id, 'name' => 'Plain Panel', 'slug' => 'plain-panel', 'price' => 3000]);

        return compact('category', 'philips', 'osram', 'deal', 'featured', 'plain');
    }

    public function test_home_shows_deals_featured_new_and_brands(): void
    {
        $this->catalog();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(["Today's Deals", 'Deal Bulb', 'Featured Products', 'Featured Tube', 'Shop by Brand', 'Philips', 'New Arrivals', 'Plain Panel'], false);
    }

    public function test_home_blocks_can_be_switched_off_and_renamed(): void
    {
        $this->catalog();
        Content::saveGroup('home', ['deals_enabled' => false, 'brands_enabled' => false, 'featured_title' => 'Top picks']);

        $this->get('/')
            ->assertDontSee("Today's Deals", false)
            ->assertDontSee('Shop by Brand')
            ->assertSee('Top picks');
    }

    public function test_brand_page_lists_only_that_brand(): void
    {
        $this->catalog();

        $this->get('/brand/philips')
            ->assertOk()
            ->assertSee('<title>Philips – Usman Electronics</title>', false)
            ->assertSee('Dutch lighting brand')
            ->assertSee('Deal Bulb')
            ->assertDontSee('Featured Tube')
            ->assertDontSee('noindex', false);

        $this->get('/brand/philips?sort=latest')->assertSee('noindex', false);
        $this->get('/brand/unknown')->assertNotFound();

        // Product page links to its brand; sitemap lists brands
        $this->get('/product/deal-bulb')->assertSee(route('brand.show', 'philips'));
        \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
        $this->get('/sitemap.xml')->assertSee(route('brand.show', 'philips'));
    }

    public function test_admin_changes_are_logged_with_old_and_new_values(): void
    {
        ['plain' => $product] = $this->catalog();
        $staff = User::factory()->create(['name' => 'Ali Staff']);
        $this->actingAs($staff);

        $product->update(['price' => 3200, 'description' => '<p>New text</p>']);

        $entry = AdminActivity::where('action', 'updated')->sole();
        $this->assertSame('Ali Staff', $entry->user_name);
        $this->assertSame('Product: Plain Panel', $entry->subject_label);
        $this->assertEquals(['old' => '3000.00', 'new' => '3200'], $entry->changes['price']);
        $this->assertSame('New text', $entry->changes['description']['new']);
    }

    public function test_order_status_change_is_logged(): void
    {
        $order = Order::create(['customer_name' => 'A', 'customer_phone' => '+923001234567']);
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('markContacted');

        $entry = AdminActivity::where('subject_type', 'Order')->latest('id')->first();
        $this->assertSame(OrderStatus::Contacted->value, $entry->changes['status']['new']);
    }

    public function test_customer_actions_and_secrets_are_not_logged(): void
    {
        $order = Order::create(['customer_name' => 'Guest', 'customer_phone' => '+923001234567']);
        $this->assertSame(0, AdminActivity::count()); // storefront orders are not admin actions

        $user = User::factory()->owner()->create();
        $this->actingAs($user);
        $user->update(['password' => 'Another-Strong-Pass-1!']);
        $user->saveAppAuthenticationSecret('SECRET123');

        $this->assertSame(0, AdminActivity::where('action', 'updated')->count());
    }

    public function test_website_settings_saves_are_logged(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(HomePage::class)
            ->fillForm(['home.hero_title' => 'New hero title'])
            ->call('save');

        $entry = AdminActivity::where('action', 'settings')->sole();
        $this->assertSame('Website: Home page', $entry->subject_label);
        $this->assertSame('New hero title', $entry->changes['home.hero_title']['new']);
    }

    public function test_only_owner_can_see_the_activity_log(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(AdminActivityResource::getUrl())->assertForbidden();

        $this->actingAs(User::factory()->owner()->create());
        $this->get(AdminActivityResource::getUrl())->assertOk();
    }

    public function test_old_entries_are_pruned_after_a_year(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        AdminActivity::record('login', 'Logged in');
        AdminActivity::query()->update(['created_at' => now()->subYears(2)]);
        AdminActivity::record('login', 'Logged in');

        $this->artisan('model:prune', ['--model' => [AdminActivity::class]])->assertSuccessful();

        $this->assertSame(1, AdminActivity::count());
    }

    public function test_times_are_shown_in_pakistan_time(): void
    {
        $this->assertSame('Asia/Karachi', config('app.timezone'));
    }
}

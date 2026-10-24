<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Pages\Website\BrandingTheme;
use App\Filament\Pages\Website\FooterContent;
use App\Filament\Pages\Website\HeaderMenu;
use App\Filament\Pages\Website\HomePage;
use App\Filament\Pages\Website\PageTexts;
use App\Filament\Pages\Website\SeoSettings;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Validation and behaviour of the admin forms, tables and filters
class AdminFormsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Wires', 'slug' => 'wires']);
        $this->actingAs(User::factory()->owner()->create());
    }

    private function productForm(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'LED Bulb 12W',
            'slug'        => 'led-bulb-12w',
            'category_id' => $this->category->id,
            'price'       => 450,
            'stock'       => 10,
            'is_active'   => true,
        ], $overrides);
    }

    public function test_product_needs_name_category_and_price(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => '', 'slug' => '', 'category_id' => null, 'price' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required', 'category_id' => 'required', 'price' => 'required']);
    }

    public function test_product_slug_must_be_url_safe_and_unique(): void
    {
        Product::create(['category_id' => $this->category->id, 'name' => 'Old', 'slug' => 'led-bulb-12w', 'price' => 1]);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productForm(['slug' => 'LED Bulb!']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productForm())
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_two_products_cannot_share_a_sku(): void
    {
        $existing = Product::create(['category_id' => $this->category->id, 'name' => 'Old', 'slug' => 'old', 'price' => 1, 'sku' => 'FC-729']);

        Livewire::test(CreateProduct::class)
            ->fillForm($this->productForm(['sku' => 'FC-729']))
            ->call('create')
            ->assertHasFormErrors(['sku' => 'unique']);

        // Saving a product with its own SKU is fine
        Livewire::test(EditProduct::class, ['record' => $existing->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_price_and_stock_cannot_be_negative(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm($this->productForm(['price' => -5, 'stock' => -1]))
            ->call('create')
            ->assertHasFormErrors(['price' => 'min', 'stock' => 'min']);
    }

    public function test_sale_price_is_required_when_on_sale(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm($this->productForm(['is_on_sale' => true, 'sale_price' => null]))
            ->call('create')
            ->assertHasFormErrors(['sale_price' => 'required']);
    }

    public function test_sub_categories_can_only_sit_under_main_categories(): void
    {
        $child = Category::create(['name' => 'House Wire', 'slug' => 'house-wire', 'parent_id' => $this->category->id]);

        // A category with sub-categories can't itself become a sub-category (no loops, no third level)
        Livewire::test(EditCategory::class, ['record' => $this->category->getRouteKey()])
            ->assertFormFieldDisabled('parent_id');

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Cables', 'slug' => 'cables', 'parent_id' => $child->id])
            ->call('create')
            ->assertHasFormErrors(['parent_id']);

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Cables', 'slug' => 'cables', 'parent_id' => $this->category->id])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_content_page_is_created(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Delivery policy', 'slug' => 'delivery-policy', 'body' => '<p>We deliver in Lahore.</p>', 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Page::class, ['slug' => 'delivery-policy']);
        $this->get('/pages/delivery-policy')->assertOk()->assertSee('We deliver in Lahore.');
    }

    public function test_new_admin_needs_a_strong_password_and_unique_email(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Staff', 'email' => auth()->user()->email, 'role' => 'staff', 'password' => 'password'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique', 'password']);
    }

    public function test_order_search_finds_any_phone_format_and_date_filter_works(): void
    {
        $old = Order::create(['customer_name' => 'Old Order', 'customer_phone' => '+923001112222', 'items_count' => 1, 'subtotal' => 100]);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();
        $new = Order::create(['customer_name' => 'New Order', 'customer_phone' => '+923214445555', 'items_count' => 1, 'subtotal' => 100]);

        Livewire::test(ListOrders::class)
            ->searchTable('0321 4445555')
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);

        Livewire::test(ListOrders::class)
            ->filterTable('created_at', ['from' => now()->subDays(7)->toDateString()])
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_quote_search_finds_local_phone_format(): void
    {
        $quote = QuoteRequest::create(['name' => 'Bilal Traders', 'phone' => '+923211234567', 'items' => '200m wire']);
        $other = QuoteRequest::create(['name' => 'Other', 'phone' => '+923339876543', 'items' => 'switches']);

        Livewire::test(ListQuoteRequests::class)
            ->searchTable('0321-1234567')
            ->assertCanSeeTableRecords([$quote])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_every_website_page_saves_with_its_defaults(): void
    {
        // Opening a page and pressing Save without changes must never hit a validation error
        foreach ([SiteSettings::class, BrandingTheme::class, HeaderMenu::class, HomePage::class, FooterContent::class, PageTexts::class, SeoSettings::class] as $page) {
            Livewire::test($page)->call('save')->assertHasNoFormErrors();
        }

        // A normal Google Maps share link survives saving (only embed codes used to be kept)
        $this->assertStringStartsWith('https://maps.app.goo.gl/', (string) \App\Models\SiteSetting::current()->fresh()->map_embed_url);
    }

    public function test_viewing_a_contact_message_marks_it_read(): void
    {
        $message = ContactMessage::create(['name' => 'Sara', 'phone' => '03001234567', 'message' => 'Do you deliver to Multan?']);

        Livewire::test(ManageContactMessages::class)
            ->callTableAction('view', $message);

        $this->assertNotNull($message->fresh()->read_at);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\ErrorLog;
use App\Models\HomepageSlider;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaunchCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemoAndTestData(): void
    {
        $this->seed();                                   // demo categories, brands, products, home sections
        SiteSetting::current();                          // placeholder emails
        Content::saveGroup('home', ['hero_subtitle' => "We're building a Laravel-powered clone of eelstore.pk"]);
        HomepageSlider::create(['title' => 'Old', 'image_path' => 'storage/hero/slide1.jpg']);
        Order::create(['customer_name' => 'Test Customer', 'customer_phone' => '+923001234567']);
        QuoteRequest::create(['name' => 'Test', 'phone' => '+923001234567', 'items' => 'x']);
        ContactMessage::create(['name' => 'T', 'email' => 't@example.com', 'message' => 'Hello there']);
        ErrorLog::forceCreate(['fingerprint' => 'x', 'source' => 'server', 'message' => 'Boom', 'last_seen_at' => now()]);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->seedDemoAndTestData();

        $this->artisan('app:launch-cleanup', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(7, Product::count());
        $this->assertSame(1, Order::count());
    }

    public function test_force_removes_demo_and_test_data_but_keeps_real_data(): void
    {
        Storage::fake('public');
        $this->seedDemoAndTestData();

        $owner = User::factory()->owner()->create();
        $realBrand = Company::create(['name' => 'Fast Cables', 'slug' => 'fast-cables']);
        $real = Product::create(['category_id' => Category::where('slug', 'wires')->value('id'), 'company_id' => $realBrand->id,
            'name' => '7/29 Wire 90m', 'slug' => '7-29-wire-90m', 'price' => 9000]);
        $goodSlide = HomepageSlider::create(['title' => 'Real', 'image_path' => 'sliders/real.jpg']);
        Storage::disk('public')->put('sliders/real.jpg', 'x');

        $this->artisan('app:launch-cleanup', ['--force' => true])->assertSuccessful();

        // Removed
        $this->assertSame([$real->id], Product::pluck('id')->all());
        $this->assertSame(0, Company::whereIn('slug', ['company-a', 'company-b'])->count());
        $this->assertSame(0, Order::count());
        $this->assertSame(0, QuoteRequest::count());
        $this->assertSame(0, ContactMessage::count());
        $this->assertSame(0, ErrorLog::count());
        $this->assertNull(SiteSetting::current()->fresh()->contact_email);
        $this->assertNull(SiteSetting::current()->fresh()->contact_recipient_email);
        $this->assertSame(config('site_content.home.hero_subtitle'), content('home.hero_subtitle'));
        $this->assertSame([$goodSlide->id], HomepageSlider::pluck('id')->all());

        // Kept
        $this->assertTrue($owner->fresh()->exists);
        $this->assertSame(4, Category::count());
        $this->assertTrue($realBrand->fresh()->exists);
    }

    public function test_nothing_to_do_on_a_clean_site(): void
    {
        $this->artisan('app:launch-cleanup', ['--force' => true])
            ->expectsOutputToContain('already clean')
            ->assertSuccessful();
    }
}

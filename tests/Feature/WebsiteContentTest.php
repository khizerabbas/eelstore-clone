<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\Website\BrandingTheme;
use App\Filament\Pages\Website\FooterContent;
use App\Filament\Pages\Website\HeaderMenu;
use App\Filament\Pages\Website\HomePage;
use App\Filament\Pages\Website\PageTexts;
use App\Filament\Pages\Website\SeoSettings;
use App\Models\Page;
use App\Models\SiteContent;
use App\Models\User;
use App\Support\Content;
use App\Support\Theme;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_defaults_are_used_until_overridden(): void
    {
        $this->assertSame(config('site_content.home.hero_title'), content('home.hero_title'));

        Content::saveGroup('home', ['hero_title' => 'Best cables in Lahore']);

        $this->assertSame('Best cables in Lahore', content('home.hero_title'));
        $this->assertSame(config('site_content.home.hero_button_label'), content('home.hero_button_label'));
    }

    public function test_text_placeholders(): void
    {
        $this->assertSame('© ' . date('Y') . ' Usman Electronics. All rights reserved.', Content::text('footer.copyright'));
    }

    public function test_links_are_restricted_to_site_paths_and_http(): void
    {
        $this->assertSame(url('/shop'), Content::link('/shop'));
        $this->assertSame('https://example.com', Content::link('https://example.com'));
        $this->assertNull(Content::link('javascript:alert(1)'));
        $this->assertNull(Content::link('//evil.example'));
        $this->assertNull(Content::link(''));
    }

    public function test_owner_can_open_every_website_page_and_staff_cannot(): void
    {
        $pages = [BrandingTheme::class, HeaderMenu::class, HomePage::class, FooterContent::class, PageTexts::class, SeoSettings::class];

        $this->actingAs(User::factory()->owner()->create());
        foreach ($pages as $page) {
            $this->get($page::getUrl())->assertOk();
        }

        $this->actingAs(User::factory()->create());
        foreach ($pages as $page) {
            $this->get($page::getUrl())->assertForbidden();
        }
    }

    public function test_home_page_edits_show_on_the_storefront(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(HomePage::class)
            ->fillForm([
                'home.hero_title'        => 'Wires & Cables Wholesale',
                'home.hero_button_label' => 'Browse catalogue',
                'home.hero_button_url'   => '/shop',
                'home.benefits'          => [
                    ['icon' => 'truck', 'title' => 'Fast delivery', 'text' => 'Across Lahore in 24 hours'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Repeater stored as a plain list
        $this->assertSame([['icon' => 'truck', 'title' => 'Fast delivery', 'text' => 'Across Lahore in 24 hours']], content('home.benefits'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Wires &amp; Cables Wholesale', false)
            ->assertSee('Browse catalogue')
            ->assertSee('Fast delivery')
            ->assertDontSee('Genuine Products');
    }

    public function test_unsafe_links_are_rejected(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(HeaderMenu::class)
            ->fillForm(['header.extra_links' => [['label' => 'Bad', 'url' => 'javascript:alert(1)']]])
            ->call('save')
            ->assertHasFormErrors();

        $this->assertNull(SiteContent::where('key', 'header.extra_links')->first());
    }

    public function test_header_and_footer_edits_show_on_the_storefront(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(HeaderMenu::class)
            ->fillForm([
                'header.topbar_message' => 'Free delivery over Rs 10,000',
                'header.nav_shop'       => 'All products',
                'header.extra_links'    => [['label' => 'Bulk orders', 'url' => '/pages/bulk-orders']],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(FooterContent::class)
            ->fillForm(['footer.copyright' => '© {year} {store} – made in Lahore'])
            ->call('save')
            ->assertHasNoFormErrors();

        Page::create(['title' => 'Delivery & COD', 'slug' => 'delivery', 'body' => '<p>We deliver</p>']);

        $this->get('/')
            ->assertSee('Free delivery over Rs 10,000')
            ->assertSee('All products')
            ->assertSee('Bulk orders')
            ->assertSee(url('/pages/bulk-orders'))
            ->assertSee('© ' . date('Y') . ' Usman Electronics – made in Lahore')
            ->assertSee('Delivery &amp; COD', false);

        $this->get('/pages/delivery')->assertOk()->assertSee('We deliver');
    }

    public function test_unpublished_pages_are_hidden(): void
    {
        Page::create(['title' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        $this->get('/pages/draft')->assertNotFound();
    }

    public function test_brand_colour_must_keep_white_text_readable(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(BrandingTheme::class)
            ->fillForm(['branding.brand_color' => '#ffff00'])
            ->call('save')
            ->assertHasFormErrors(['branding.brand_color']);

        Livewire::test(BrandingTheme::class)
            ->fillForm(['branding.brand_color' => '#1e3a8a', 'branding.accent_color' => '#22c55e'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertSee('--color-brand: 30 58 138;', false);
    }

    public function test_theme_picks_readable_text_colours(): void
    {
        Content::saveGroup('branding', ['accent_color' => '#fbbf24', 'brand_color' => '#0f172a']);
        $this->assertStringContainsString('--color-on-accent: 15 23 42;', Theme::cssVariables()); // dark text on yellow

        Content::saveGroup('branding', ['accent_color' => '#1d4ed8']);
        $this->assertStringContainsString('--color-on-accent: 255 255 255;', Theme::cssVariables()); // white text on blue

        // accent-ink is always readable on white
        preg_match('/--color-accent-ink: (\d+) (\d+) (\d+);/', Theme::cssVariables(), $m);
        $hex = sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]);
        $this->assertGreaterThanOrEqual(4.5, Theme::contrast($hex, '#ffffff'));
    }

    public function test_order_status_messages_are_editable(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(PageTexts::class)
            ->fillForm(['order_status.contacted' => 'We sent you a WhatsApp – please reply to confirm.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('We sent you a WhatsApp – please reply to confirm.', OrderStatus::Contacted->customerMessage());
    }

    public function test_reset_restores_defaults(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        Content::saveGroup('seo', ['home_title' => 'Custom title']);

        Livewire::test(SeoSettings::class)->call('resetToDefaults');

        $this->assertSame(config('site_content.seo.home_title'), content('seo.home_title'));
    }

    public function test_missing_images_use_the_placeholder(): void
    {
        $category = \App\Models\Category::create(['name' => 'Lighting', 'slug' => 'lighting']);
        \App\Models\Product::create(['category_id' => $category->id, 'name' => 'LED Bulb', 'slug' => 'led-bulb', 'price' => 300]);

        $placeholder = asset('images/no-preview.svg');

        // No image set → placeholder image (not an empty box)
        $this->get('/shop')->assertSee('src="' . $placeholder . '"', false);
        $this->get('/product/led-bulb')->assertSee('src="' . $placeholder . '"', false);

        // Every image carries the fallback for files that fail to load
        $this->get('/product/led-bulb')->assertSee('data-fallback="' . $placeholder . '"', false);

        // Hero without slides shows the placeholder too
        $this->get('/')->assertSee('src="' . $placeholder . '"', false);
    }

    public function test_logo_with_missing_file_falls_back_to_letters(): void
    {
        Content::saveGroup('branding', ['logo' => 'branding/does-not-exist.png', 'logo_text' => 'UE']);

        $this->assertNull(Content::imageUrl('branding.logo'));
        $this->get('/')->assertDontSee('does-not-exist.png');
    }

    public function test_seo_tags_are_rendered(): void
    {
        $this->get('/')
            ->assertSee('<title>Usman Electronics – Electrical Supplies in Lahore</title>', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false);
    }
}

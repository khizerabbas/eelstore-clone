<?php

namespace App\Console\Commands;

use App\Models\AdminActivity;
use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\ErrorLog;
use App\Models\HomepageCategorySection;
use App\Models\HomepageSlider;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\SiteContent;
use App\Models\SiteSetting;
use App\Support\Content;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

/**
 * php artisan app:launch-cleanup [--dry-run] [--force]
 * Removes demo/test data before the site goes live (see docs/GO_LIVE.md §8). Categories and admin users are kept.
 */
class LaunchCleanup extends Command
{
    protected $signature = 'app:launch-cleanup
        {--dry-run : Only show what would be removed}
        {--force : Remove everything listed without asking}';

    protected $description = 'Remove demo products, test orders/quotes/messages, logs and placeholder settings before launch';

    // Products and brands created by the demo seeders
    private const DEMO_PRODUCTS = [
        '1 Pair Wire (Company A)', '2 Pair Wire (Company A)', '1 Pair Wire (Company B)', '4 Pair Wire (Company B)',
        '12W LED Bulb', '18W Tube Light', 'LED Panel Light 2x2',
    ];

    private const DEMO_BRANDS = ['company-a', 'company-b'];

    private const PLACEHOLDER_EMAILS = ['info@example.com', 'test@test.com'];

    public function handle(): int
    {
        $sections = $this->sections();

        $this->info('Launch clean-up – this is what can be removed:');
        $this->table(['Section', 'What', 'Count'], collect($sections)->map(fn ($s) => [$s['label'], $s['what'], $s['count']])->all());

        if ($this->option('dry-run')) {
            $this->comment('Dry run – nothing was changed.');

            return self::SUCCESS;
        }

        $available = collect($sections)->filter(fn ($s) => $s['count'] > 0);

        if ($available->isEmpty()) {
            $this->info('Nothing to clean – the site is already clean.');

            return self::SUCCESS;
        }

        $chosen = $this->option('force')
            ? $available->keys()->all()
            : multiselect(
                label: 'Which sections should be removed?',
                options: $available->map(fn ($s) => "{$s['label']} ({$s['count']})")->all(),
                default: $available->keys()->all(),
                hint: 'Space to toggle, Enter to continue',
            );

        if (! $chosen) {
            $this->comment('Nothing selected – nothing was changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! confirm('This permanently deletes the selected data. Continue?', default: false)) {
            $this->comment('Cancelled – nothing was changed.');

            return self::SUCCESS;
        }

        foreach ($chosen as $key) {
            ($sections[$key]['run'])();
            $this->line("  ✓ {$sections[$key]['label']}");
        }

        Content::flush();
        Cache::forget('sitemap.xml');

        $this->info('Done. Next: php artisan optimize, then fill in the real store details in the admin (docs/GO_LIVE.md §8).');

        return self::SUCCESS;
    }

    private function sections(): array
    {
        $demoProducts = Product::whereIn('name', self::DEMO_PRODUCTS);
        $demoBrands   = Company::whereIn('slug', self::DEMO_BRANDS);
        $settings     = SiteSetting::current();

        $placeholderFields = collect(['contact_email', 'contact_recipient_email'])
            ->filter(fn ($f) => in_array($settings->{$f}, self::PLACEHOLDER_EMAILS, true));
        $demoHero = SiteContent::where('key', 'home.hero_subtitle')->get()
            ->filter(fn ($c) => Str::contains((string) $c->value, ['Laravel-powered clone', 'eelstore.pk']));
        $brokenSlides = HomepageSlider::all()->filter(fn (HomepageSlider $s) => ! $this->slideFileExists($s));
        $orphanSections = HomepageCategorySection::whereNull('category_id');

        return [
            'demo_catalog' => [
                'label' => 'Demo products & brands',
                'what'  => 'Products/brands from the demo seeders (with their images)',
                'count' => (clone $demoProducts)->count() + (clone $demoBrands)->whereDoesntHave('products', fn ($q) => $q->whereNotIn('name', self::DEMO_PRODUCTS))->count(),
                'run'   => function () use ($demoProducts, $demoBrands) {
                    (clone $demoProducts)->get()->each->delete(); // model events remove image files
                    (clone $demoBrands)->doesntHave('products')->get()->each->delete();
                },
            ],
            'orders' => [
                'label' => 'Test orders',
                'what'  => 'All order requests and their items',
                'count' => Order::count(),
                'run'   => fn () => Order::query()->each(fn (Order $o) => $o->delete()),
            ],
            'quotes' => [
                'label' => 'Test quote requests',
                'what'  => 'All quote requests (and uploaded list files)',
                'count' => QuoteRequest::count(),
                'run'   => fn () => QuoteRequest::query()->each(fn (QuoteRequest $q) => $q->delete()),
            ],
            'messages' => [
                'label' => 'Contact messages',
                'what'  => 'All contact form messages',
                'count' => ContactMessage::count(),
                'run'   => fn () => ContactMessage::query()->delete(),
            ],
            'logs' => [
                'label' => 'Logs & queues',
                'what'  => 'Error log, activity log, pending/failed jobs, import/export history',
                'count' => ErrorLog::count() + AdminActivity::count() + DB::table('jobs')->count() + DB::table('failed_jobs')->count()
                    + DB::table('imports')->count() + DB::table('exports')->count(),
                'run'   => function () {
                    ErrorLog::query()->delete();
                    AdminActivity::query()->delete();
                    DB::table('jobs')->delete();
                    DB::table('failed_jobs')->delete();
                    DB::table('failed_import_rows')->delete();
                    DB::table('imports')->delete();
                    DB::table('exports')->delete();
                    DB::table('notifications')->delete();
                },
            ],
            'placeholders' => [
                'label' => 'Placeholder settings',
                'what'  => 'Example emails (' . implode(', ', self::PLACEHOLDER_EMAILS) . '), demo hero text, slides with missing images, empty home sections',
                'count' => $placeholderFields->count() + $demoHero->count() + $brokenSlides->count() + (clone $orphanSections)->count(),
                'run'   => function () use ($settings, $placeholderFields, $demoHero, $brokenSlides, $orphanSections) {
                    foreach ($placeholderFields as $field) {
                        $settings->{$field} = null;
                    }
                    $settings->save();
                    $demoHero->each->delete();
                    $brokenSlides->each->delete();
                    (clone $orphanSections)->delete();
                },
            ],
        ];
    }

    private function slideFileExists(HomepageSlider $slide): bool
    {
        if (str_starts_with($slide->image_path, 'storage/')) {
            return Storage::disk('public')->exists(Str::after($slide->image_path, 'storage/'));
        }

        return Storage::disk('public')->exists($slide->image_path);
    }
}

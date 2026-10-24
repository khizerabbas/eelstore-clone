<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Services\WhatsApp\CallMeBotWhatsAppNotifier;
use App\Services\WhatsApp\CloudApiWhatsAppNotifier;
use App\Services\WhatsApp\LogWhatsAppNotifier;
use App\Services\WhatsApp\WhatsAppNotifier;
use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use Illuminate\Support\Facades\View;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Owner WhatsApp alerts – driver chosen by WHATSAPP_DRIVER (log | callmebot | cloud_api)
        $this->app->bind(WhatsAppNotifier::class, function () {
            $config = config('orders.whatsapp');

            return match ($config['driver']) {
                'callmebot' => new CallMeBotWhatsAppNotifier(
                    $config['callmebot']['phone'] ?? null,
                    $config['callmebot']['api_key'] ?? null,
                ),
                'cloud_api' => new CloudApiWhatsAppNotifier($config['cloud_api'] ?? []),
                default     => new LogWhatsAppNotifier(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Record admin logins (shown in the admin users list)
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                \App\Models\AdminActivity::record('login', 'Logged in');
            }
        });

        View::composer('layouts.frontend', function ($view) {
            // site settings (phone, email, socials, map etc.)
            $settings = SiteSetting::current();

            // categories for the header search dropdown and menus (admin sort order)
            $searchCategories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            // footer: top-level categories and published CMS pages marked "Link in the footer"
            $footerCategories = $searchCategories
                ->whereNull('parent_id')
                ->take((int) content('footer.categories_limit', 6));

            $footerPages = Page::where('is_published', true)
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->get(['title', 'slug']);

            $view->with([
                'settings'         => $settings,
                'searchCategories' => $searchCategories,
                'footerCategories' => $footerCategories,
                'footerPages'      => $footerPages,
            ]);
        });
    }
}

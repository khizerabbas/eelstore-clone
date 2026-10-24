<?php

namespace App\Providers;

use App\Models\SiteSetting;
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
        View::composer('layouts.frontend', function ($view) {
            // site settings (phone, email, socials, map etc.)
            $settings = SiteSetting::current();

            // categories for the header search dropdown
            $searchCategories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $view->with([
                'settings'         => $settings,
                'searchCategories' => $searchCategories,
            ]);
        });
    }
}

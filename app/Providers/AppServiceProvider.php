<?php

namespace App\Providers;

use App\Models\SiteSetting;
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
        //
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

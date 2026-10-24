<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\HomepageCategorySection;
use App\Models\HomepageSlider;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $heroSliders = HomepageSlider::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        $limit  = max(1, min(12, (int) content('home.block_limit', 8)));
        $active = fn () => Product::with('company')->where('is_active', true);

        // Product blocks (Website → Home page): each can be switched off
        $deals = content('home.deals_enabled')
            ? $active()->where('is_on_sale', true)->where('sale_price', '>', 0)->latest('updated_at')->take($limit)->get()
            : collect();

        $featured = content('home.featured_enabled')
            ? $active()->where('is_featured', true)->latest('updated_at')->take($limit)->get()
            : collect();

        $newArrivals = content('home.new_enabled')
            ? $active()->latest()->take($limit)->get()
            : collect();

        $brands = content('home.brands_enabled')
            ? Company::where('is_active', true)
                ->whereHas('products', fn ($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get()
            : collect();

        // Category rows (Website → Home sections)
        $homepageCategorySections = HomepageCategorySection::with('category')
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->filter(fn ($section) => $section->category !== null)
            ->values();

        $homepageCategorySections->each(function ($section) {
            $section->products = Product::with('company')
                ->where('is_active', true)
                ->where('category_id', $section->category_id)
                ->latest()
                ->take(8)
                ->get();
        });

        return view('home', compact('heroSliders', 'deals', 'featured', 'newArrivals', 'brands', 'homepageCategorySections'));
    }
}

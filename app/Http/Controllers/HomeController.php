<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlider;
use App\Models\Product;
use App\Models\HomepageCategorySection;
class HomeController extends Controller
{
    public function index()
    {

        $heroSliders = HomepageSlider::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        // Load up to 3 homepage category sections, with categories
        $homepageCategorySections = HomepageCategorySection::with('category')
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            // hide sections where no category is selected
            ->filter(fn ($section) => $section->category !== null)
            ->values();

        // Attach products for each section/category
        $homepageCategorySections->each(function ($section) {
            $section->products = Product::query()
                ->where('is_active', true)
                ->where('category_id', $section->category_id) // adjust if you use a pivot/other relation
                ->latest()
                ->take(8)
                ->get();
        });

        return view('home', compact('heroSliders', 'homepageCategorySections'));
    }
}

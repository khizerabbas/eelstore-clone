<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlider;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Featured wires (with companies)
        $featuredWires = Product::with(['company', 'category'])
            ->whereHas('category', fn($q) => $q->where('slug', 'wires'))
            ->where('is_active', true)
            ->where('is_featured', true)
            ->take(8)
            ->get();

        // Featured lighting (no companies required)
        $featuredLighting = Product::with(['category'])
            ->whereHas('category', fn($q) => $q->where('slug', 'lighting'))
            ->where('is_active', true)
            ->where('is_featured', true)
            ->take(8)
            ->get();

        // General featured products (for a “Featured” section)
        $featuredAll = Product::with(['category', 'company'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->take(12)
            ->get();

        $heroSliders = HomepageSlider::where('is_active', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();


        return view('home', compact('featuredWires', 'featuredLighting', 'featuredAll','heroSliders'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = Product::with('company')
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();

        $hasCompanies = $products->whereNotNull('company_id')->isNotEmpty();

        return view('category.show', compact('category', 'products', 'hasCompanies'));
    }
}

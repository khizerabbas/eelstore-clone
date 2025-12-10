<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Start with current query string (?sort=..., ?per_page=..., etc.)
        $query = $request->query();

        // Make sure categories[] is an array and contains this category ID
        $selected = (array) ($query['categories'] ?? []);

        if (! in_array($category->id, $selected)) {
            $selected[] = $category->id;
        }

        $query['categories'] = $selected;

        // Forward to the shop page with this category pre-selected
        return redirect()->route('shop.index', $query);
    }
}

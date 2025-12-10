<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, string $slug)
    {
        // Find the category by slug
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Carry over existing query params (?sort=..., ?per_page=..., etc.)
        $query = $request->query();

        // Ensure categories[] is an array
        $selected = (array) ($query['categories'] ?? []);

        // Add this category ID if not already in the list
        if (! in_array($category->id, $selected)) {
            $selected[] = $category->id;
        }

        $query['categories'] = $selected;

        // Redirect to the shop page with this category pre-selected
        return redirect()->route('shop.index', $query);
    }
}

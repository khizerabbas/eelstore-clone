<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

// Indexable category pages (/category/{slug}): the shop listing pre-filtered to this category and its sub-categories
class CategoryController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $request->has('categories')) {
            $ids = Category::where('parent_id', $category->id)
                ->where('is_active', true)
                ->pluck('id')
                ->prepend($category->id)
                ->all();

            $request->merge(['categories' => $ids]);
        }

        return app(ProductController::class)->index($request, $category);
    }
}

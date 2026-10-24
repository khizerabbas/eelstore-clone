<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

// Indexable brand pages (/brand/{slug}): the shop listing pre-filtered to one brand ("company" in the database)
class BrandController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $brand = Company::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $request->has('companies')) {
            $request->merge(['companies' => [$brand->id]]);
        }

        return app(ProductController::class)->index($request, currentBrand: $brand);
    }
}

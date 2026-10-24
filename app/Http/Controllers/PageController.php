<?php

namespace App\Http\Controllers;

use App\Models\Page;

// Public CMS pages created in the admin (About, Delivery & COD, Returns, Privacy …)
class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}

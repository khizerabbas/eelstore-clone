<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageCategorySection;
use Illuminate\Database\Seeder;

class HomepageCategorySectionSeeder extends Seeder
{
    public function run(): void
    {
        // Don't overwrite sections that were already configured
        if (HomepageCategorySection::exists()) {
            return;
        }

        // Initial 3 sections using the first 3 active categories
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(3)
            ->get();

        $position = 1;

        foreach ($categories as $category) {
            HomepageCategorySection::create([
                'category_id' => $category->id,
                'position'    => $position++,
                'is_active'   => true,
            ]);
        }
    }
}

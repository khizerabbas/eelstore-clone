<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Wires',
                'slug' => 'wires',
                'description' => 'Electrical wires of various companies and sizes.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Lighting',
                'slug' => 'lighting',
                'description' => 'Bulbs, tube lights, LED panels and more.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Switch & Socket',
                'slug' => 'switch-socket',
                'description' => 'Switches, sockets, plates and related accessories.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Misc electrical accessories and tools.',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name'        => $data['name'],
                    'description' => $data['description'] ?? null,
                    'sort_order'  => $data['sort_order'] ?? 0,
                    'is_active'   => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $wiresCategory    = Category::where('slug', 'wires')->first();
        $lightingCategory = Category::where('slug', 'lighting')->first();

        $companyA = Company::where('slug', 'company-a')->first();
        $companyB = Company::where('slug', 'company-b')->first();

        if (! $wiresCategory || ! $lightingCategory) {
            return;
        }

        // --- Wires with companies ---
        $wireProducts = [
            [
                'name'       => '1 Pair Wire (Company A)',
                'company'    => $companyA,
                'price'      => 2500,
                'sale_price' => 2200,
                'is_on_sale' => true,
            ],
            [
                'name'       => '2 Pair Wire (Company A)',
                'company'    => $companyA,
                'price'      => 3500,
                'sale_price' => null,
                'is_on_sale' => false,
            ],
            [
                'name'       => '1 Pair Wire (Company B)',
                'company'    => $companyB,
                'price'      => 2400,
                'sale_price' => 2100,
                'is_on_sale' => true,
            ],
            [
                'name'       => '4 Pair Wire (Company B)',
                'company'    => $companyB,
                'price'      => 4500,
                'sale_price' => null,
                'is_on_sale' => false,
            ],
        ];

        foreach ($wireProducts as $data) {
            Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id'       => $wiresCategory->id,
                    'company_id'        => $data['company']?->id,
                    'name'              => $data['name'],
                    'sku'               => 'WIRE-' . strtoupper(Str::random(6)),
                    'price'             => $data['price'],
                    'sale_price'        => $data['sale_price'],
                    'is_on_sale'        => $data['is_on_sale'],
                    'stock'             => 100,
                    'is_active'         => true,
                    'is_featured'       => true,
                    'short_description' => 'High quality wire for residential use.',
                    'description'       => 'Demo product seeded for Usman Electronics wires category.',
                    'image'             => null,
                ]
            );
        }

        // --- Lighting products without company (company_id = null) ---
        $lightingProducts = [
            [
                'name'       => '12W LED Bulb',
                'price'      => 350,
                'sale_price' => 320,
                'is_on_sale' => true,
                'is_featured' => true,
            ],
            [
                'name'       => '18W Tube Light',
                'price'      => 900,
                'sale_price' => null,
                'is_on_sale' => false,
            ],
            [
                'name'       => 'LED Panel Light 2x2',
                'price'      => 3200,
                'sale_price' => 2999,
                'is_on_sale' => true,
            ],
        ];

        foreach ($lightingProducts as $index => $data) {
            Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id'       => $lightingCategory->id,
                    'company_id'        => null,
                    'name'              => $data['name'],
                    'sku'               => 'LIGHT-' . strtoupper(Str::random(6)),
                    'price'             => $data['price'],
                    'sale_price'        => $data['sale_price'],
                    'is_on_sale'        => $data['is_on_sale'],
                    'stock'             => 50,
                    'is_active'         => true,
                    'is_featured'       => $index === 0, // first lighting product featured
                    'short_description' => 'Demo lighting product for Usman Electronics.',
                    'description'       => 'Demo product seeded for lighting category.',
                    'image'             => null,
                ]
            );
        }

    }
}

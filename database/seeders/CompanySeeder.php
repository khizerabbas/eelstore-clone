<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Company A',
                'slug' => 'company-a',
                'description' => 'Sample wire manufacturer Company A.',
            ],
            [
                'name' => 'Company B',
                'slug' => 'company-b',
                'description' => 'Sample wire manufacturer Company B.',
            ],
            // add more later as needed
        ];

        foreach ($companies as $data) {
            Company::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name'        => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_active'   => true,
                ]
            );
        }
    }
}

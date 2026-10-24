<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Company;
use App\Models\HomepageSlider;
use App\Models\Product;
use App\Support\ImageVariants;
use Illuminate\Console\Command;

// php artisan app:optimize-images [--force] – creates the WebP copies for all existing uploads
class OptimizeImages extends Command
{
    protected $signature = 'app:optimize-images {--force : Recreate copies that already exist}';

    protected $description = 'Create small WebP copies of uploaded product, category, brand and slider images';

    public function handle(): int
    {
        $done = $failed = 0;

        foreach ([Product::class, Category::class, Company::class, HomepageSlider::class] as $model) {
            $model::query()->each(function ($record) use (&$done, &$failed) {
                foreach ($record->imagePaths() as $path) {
                    ImageVariants::generate($path, (bool) $this->option('force')) ? $done++ : $failed++;
                }
            });
        }

        $this->info("Optimised {$done} image(s)." . ($failed ? " {$failed} could not be read (missing or not an image)." : ''));

        return self::SUCCESS;
    }
}

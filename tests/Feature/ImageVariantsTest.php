<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\ImageVariants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function upload(string $path, int $width = 2400, int $height = 1600): string
    {
        Storage::disk('public')->put($path, UploadedFile::fake()->image('x.jpg', $width, $height)->getContent());

        return $path;
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'wires'], ['name' => 'Wires']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name'        => 'Wire',
            'slug'        => 'wire-' . uniqid(),
            'price'       => 100,
        ], $attributes));
    }

    public function test_saving_a_product_creates_small_webp_copies(): void
    {
        $product = $this->product(['image' => $this->upload('products/main.jpg'), 'gallery' => [$this->upload('products/gallery/a.jpg')]]);

        $disk = Storage::disk('public');
        $disk->assertExists('variants/thumb/products/main.webp');
        $disk->assertExists('variants/large/products/main.webp');
        $disk->assertExists('variants/thumb/products/gallery/a.webp');

        [$width] = getimagesizefromstring($disk->get('variants/thumb/products/main.webp'));
        $this->assertSame(480, $width);

        $this->assertStringEndsWith('variants/thumb/products/main.webp', $product->imageUrl());
        $this->assertStringEndsWith('variants/large/products/main.webp', $product->imageUrls()[0]);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        $this->product(['image' => $this->upload('products/small.jpg', 300, 200)]);

        [$width] = getimagesizefromstring(Storage::disk('public')->get('variants/large/products/small.webp'));
        $this->assertSame(300, $width);
    }

    public function test_replaced_and_deleted_images_are_cleaned_up(): void
    {
        $product = $this->product(['image' => $this->upload('products/old.jpg')]);

        $product->update(['image' => $this->upload('products/new.jpg')]);

        $disk = Storage::disk('public');
        $disk->assertMissing('products/old.jpg');
        $disk->assertMissing('variants/thumb/products/old.webp');
        $disk->assertExists('variants/thumb/products/new.webp');

        $product->delete();
        $disk->assertMissing('products/new.jpg');
        $disk->assertMissing('variants/large/products/new.webp');
    }

    public function test_falls_back_to_original_when_no_copy_exists(): void
    {
        Storage::disk('public')->put('products/raw.jpg', 'not really an image');

        $this->assertStringEndsWith('products/raw.jpg', ImageVariants::url('products/raw.jpg', 'thumb'));
        $this->assertNull(ImageVariants::url(null, 'thumb'));
    }

    public function test_optimize_command_backfills_copies(): void
    {
        $product = $this->product();
        $path = $this->upload('products/legacy.jpg');
        Product::withoutEvents(fn () => $product->update(['image' => $path]));

        Storage::disk('public')->assertMissing('variants/thumb/products/legacy.webp');

        $this->artisan('app:optimize-images')->assertSuccessful();

        Storage::disk('public')->assertExists('variants/thumb/products/legacy.webp');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasImageVariants;
use App\Support\ImageVariants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasImageVariants;

    protected function imageAttributes(): array
    {
        return ['image', 'gallery'];
    }

    protected $fillable = [
        'category_id',
        'company_id',
        'name',
        'slug',
        'sku',
        'price',
        'sale_price',
        'is_on_sale',
        'stock',
        'is_active',
        'is_featured',
        'short_description',
        'description',
        'image',
        'gallery',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'gallery'     => 'array',
            'is_on_sale'  => 'boolean',
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Single price rule: sale price only counts when the product is flagged on sale.
    // Keep effectivePriceSql() in sync with this for DB filtering/sorting.
    public function getDisplayPrice(): float
    {
        return $this->is_on_sale && $this->sale_price
            ? (float) $this->sale_price
            : (float) $this->price;
    }

    // Main image (WebP copy of the requested size when available) or null
    public function imageUrl(string $size = 'thumb'): ?string
    {
        return ImageVariants::url($this->image, $size);
    }

    // Main image followed by gallery images
    public function imageUrls(string $size = 'large'): array
    {
        return collect([$this->image])
            ->merge($this->gallery ?? [])
            ->filter()
            ->unique()
            ->map(fn (string $path) => ImageVariants::url($path, $size))
            ->values()
            ->all();
    }

    // SQL version of getDisplayPrice() for whereBetween / orderBy / min / max
    public static function effectivePriceSql(): string
    {
        return 'CASE WHEN is_on_sale = 1 AND sale_price > 0 THEN sale_price ELSE price END';
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use App\Models\Concerns\HasImageVariants;
use App\Support\ImageVariants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasImageVariants, LogsAdminActivity;

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
        'size_group',
        'size_label',
        'customer_note',
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

    // Other visible products in the same size group (e.g. the 180 m coil of the same wire), cheapest first
    public function otherSizes()
    {
        if (blank($this->size_group)) {
            return collect();
        }

        return static::where('size_group', $this->size_group)
            ->where('is_active', true)
            ->whereKeyNot($this->getKey())
            ->orderByRaw(static::effectivePriceSql())
            ->get();
    }

    // Short text for size buttons
    public function sizeLabel(): string
    {
        return $this->size_label ?: $this->name;
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

    // On sale = flagged AND has a sale price (same rule as getDisplayPrice())
    public function isOnSale(): bool
    {
        return $this->is_on_sale && (float) $this->sale_price > 0 && (float) $this->sale_price < (float) $this->price;
    }

    // "12" for a 12% discount, null when not on sale
    public function discountPercent(): ?int
    {
        return $this->isOnSale() ? (int) round((1 - (float) $this->sale_price / (float) $this->price) * 100) : null;
    }

    // SQL version of getDisplayPrice() for whereBetween / orderBy / min / max
    public static function effectivePriceSql(): string
    {
        return 'CASE WHEN is_on_sale = 1 AND sale_price > 0 THEN sale_price ELSE price END';
    }
}

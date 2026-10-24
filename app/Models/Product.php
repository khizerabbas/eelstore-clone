<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

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

    // Uploaded main image (public disk) or null
    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    // Main image followed by gallery images
    public function imageUrls(): array
    {
        return collect([$this->image])
            ->merge($this->gallery ?? [])
            ->filter()
            ->unique()
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->values()
            ->all();
    }

    // SQL version of getDisplayPrice() for whereBetween / orderBy / min / max
    public static function effectivePriceSql(): string
    {
        return 'CASE WHEN is_on_sale = 1 AND sale_price > 0 THEN sale_price ELSE price END';
    }
}

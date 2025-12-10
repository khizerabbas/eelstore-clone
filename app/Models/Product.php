<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // helper for display
    public function getDisplayPrice(): float
    {
        return $this->is_on_sale && $this->sale_price
            ? (float) $this->sale_price
            : (float) $this->price;
    }
}

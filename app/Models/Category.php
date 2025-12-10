<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'is_active',
        'sort_order',
    ];

    // products in this category
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // child categories (if you ever use them)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // parent category (if nested)
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}

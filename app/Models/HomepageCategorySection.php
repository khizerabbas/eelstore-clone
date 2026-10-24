<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageCategorySection extends Model
{
    protected $fillable = [
        'category_id',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

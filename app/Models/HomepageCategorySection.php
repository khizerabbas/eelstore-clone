<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class HomepageCategorySection extends Model
{
    use LogsAdminActivity;

    protected $fillable = [
        'category_id',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function activityLabel(): string
    {
        return 'Home section: ' . ($this->category?->name ?? '#' . $this->id);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

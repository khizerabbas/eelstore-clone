<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use App\Models\Concerns\HasImageVariants;
use App\Support\ImageVariants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory, HasImageVariants, LogsAdminActivity;

    protected function imageAttributes(): array
    {
        return ['logo'];
    }

    public function imageUrl(string $size = 'thumb'): ?string
    {
        return ImageVariants::url($this->logo, $size);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

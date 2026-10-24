<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use App\Models\Concerns\HasImageVariants;
use App\Support\ImageVariants;
use Illuminate\Database\Eloquent\Model;

class HomepageSlider extends Model
{
    use HasImageVariants, LogsAdminActivity;

    protected function imageAttributes(): array
    {
        return ['image_path'];
    }

    protected $fillable = [
        'title',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function activityLabel(): string
    {
        return 'Home slide: ' . ($this->title ?: '#' . $this->id);
    }

    // Works for uploads on the public disk ("sliders/x.jpg") and legacy paths ("storage/hero/x.jpg")
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http') || str_starts_with($this->image_path, 'storage/')) {
            return asset($this->image_path);
        }

        return ImageVariants::url($this->image_path, 'large');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSlider extends Model
{
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

    // Works for uploads on the public disk ("sliders/x.jpg") and legacy paths ("storage/hero/x.jpg")
    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http') || str_starts_with($this->image_path, 'storage/')) {
            return asset($this->image_path);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }
}

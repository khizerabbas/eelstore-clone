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
}

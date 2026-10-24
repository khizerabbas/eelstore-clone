<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One editable storefront value (see App\Support\Content and config/site_content.php)
class SiteContent extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}

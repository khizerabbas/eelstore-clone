<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// CMS pages managed in the admin panel (About, Delivery & COD, Returns, Privacy, Terms, FAQ …)
class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'meta_title',
        'meta_description',
        'is_published',
        'show_in_footer',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published'   => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }
}

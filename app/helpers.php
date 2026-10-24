<?php

use App\Support\Content;

if (! function_exists('content')) {
    // Editable storefront content, e.g. content('home.hero_title') – see config/site_content.php
    function content(string $key, mixed $default = null): mixed
    {
        return Content::get($key, $default);
    }
}

<?php

use App\Support\Content;

if (! function_exists('content')) {
    // Editable storefront content, e.g. content('home.hero_title') – see config/site_content.php
    function content(string $key, mixed $default = null): mixed
    {
        return Content::get($key, $default);
    }
}

if (! function_exists('rupees')) {
    // Prices on the storefront: "Rs 2,200" (no decimals – PKR prices are whole rupees)
    function rupees(int|float|string|null $amount): string
    {
        return 'Rs ' . number_format(round((float) $amount));
    }
}

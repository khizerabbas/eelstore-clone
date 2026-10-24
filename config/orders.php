<?php

return [

    /*
    | Where new order requests are emailed. Falls back to the contact recipient in site settings.
    */
    'admin_email' => env('ORDER_ADMIN_EMAIL'),

    /*
    | Owner WhatsApp alert for new order requests.
    | Drivers: "log" (writes to the log – default/local), "callmebot", "cloud_api" (official Meta WhatsApp Cloud API).
    */
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),

        // CallMeBot – free, sends to your own number. Get the API key by messaging the bot:
        // https://www.callmebot.com/blog/free-api-whatsapp-messages/
        'callmebot' => [
            'phone'   => env('CALLMEBOT_PHONE'),   // owner number, e.g. +923001234567
            'api_key' => env('CALLMEBOT_API_KEY'),
        ],

        // Official WhatsApp Cloud API (Meta). Business-initiated messages need an approved template
        // with one body parameter, which receives the alert text.
        'cloud_api' => [
            'token'           => env('WHATSAPP_CLOUD_TOKEN'),
            'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
            'to'              => env('WHATSAPP_CLOUD_TO'), // owner number, e.g. +923001234567
            'template'        => env('WHATSAPP_CLOUD_TEMPLATE', 'new_order_alert'),
            'language'        => env('WHATSAPP_CLOUD_TEMPLATE_LANG', 'en'),
            'api_version'     => env('WHATSAPP_CLOUD_API_VERSION', 'v21.0'),
        ],
    ],

    /*
    | Cloudflare Turnstile (optional bot protection on checkout). Leave empty to disable.
    */
    'turnstile' => [
        'site_key'   => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    /*
    | Browser-only "My Orders": cookie with the customer's order tokens.
    */
    'history_cookie'         => 'ue_orders',
    'history_cookie_minutes' => 60 * 24 * 365,
    'history_max_orders'     => 20,

    /*
    | Abuse limits for order requests.
    */
    'limits' => [
        'per_ip_per_hour'    => 5,
        'per_phone_per_hour' => 3,
    ],
];

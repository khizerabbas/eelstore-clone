<?php

return [

    // Save errors to the database (Settings → Error log). Turn off only for troubleshooting.
    'enabled' => env('ERROR_LOG_ENABLED', true),

    // Errors not seen for this many days are deleted automatically (daily scheduler)
    'retention_days' => (int) env('ERROR_LOG_RETENTION_DAYS', 30),

    // Customer-facing HTTP problems to record. 404 is off by default: bots probing for
    // wp-admin etc. would fill the log. 419 = page expired (form open too long), 429 = rate limited.
    'http_statuses' => array_map('intval', array_filter(explode(',', env('ERROR_LOG_HTTP_STATUSES', '419,429')))),

    // Email the owner when a NEW error appears (or a resolved one comes back). Falls back to ORDER_ADMIN_EMAIL.
    'alert_email' => env('ERROR_ALERT_EMAIL'),
    'alerts_enabled' => env('ERROR_ALERTS_ENABLED', true),
    'alert_sources' => ['server', 'queue', 'console'],   // browser/HTTP warnings stay in the log only
    'alerts_per_hour' => 10,                             // never more than this many alert emails per hour

    // Browser errors accepted per visitor per minute (reports beyond this are dropped)
    'browser_reports_per_minute' => 20,

    // Request fields never stored
    'hidden_fields' => [
        'password', 'password_confirmation', 'current_password', 'currentPassword',
        '_token', 'token', 'secret', 'api_key', 'apikey', 'cf-turnstile-response', 'website',
        'app_authentication_secret', 'recovery_code', 'code',
    ],
];

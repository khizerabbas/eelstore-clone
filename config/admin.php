<?php

return [

    /*
    | Force every admin to set up authenticator-app 2FA on first login.
    | Defaults to on in production; turn off locally with ADMIN_REQUIRE_2FA=false.
    */
    'require_2fa' => env('ADMIN_REQUIRE_2FA', env('APP_ENV') === 'production'),

    /*
    | Optional first admin for `php artisan db:seed --class=AdminUserSeeder` (created as Owner).
    | Prefer `php artisan app:create-admin` on servers.
    */
    'seed_email'    => env('SEED_ADMIN_EMAIL'),
    'seed_password' => env('SEED_ADMIN_PASSWORD'),
];

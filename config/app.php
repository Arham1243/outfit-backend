<?php

return [
    'timezone' => 'UTC',
    'name' => env('APP_NAME', 'Laravel'),
    'url' => env('APP_URL', 'http://localhost'),
    'mobile_api_base_url' => env('MOBILE_API_BASE_URL', 'https://app.servicore.io/api'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'mail_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];

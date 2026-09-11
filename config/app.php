<?php

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'Asia/Kuala_Lumpur',

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

    'mobile_app_version' => env('MOBILE_APP_VERSION', '1.0.0'),

    'mobile_tablet_app_version' => env('MOBILE_TABLET_APP_VERSION', '1.0.0'),

    'mobile_apple_app_store_id' => env('MOBILE_APPLE_APP_STORE_ID', null),

    'mobile_android_package_name' => env('MOBILE_ANDROID_PACKAGE_NAME', null),

    'tablet_android_package_name' => env('TABLET_ANDROID_PACKAGE_NAME', null),
];

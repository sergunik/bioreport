<?php

declare(strict_types=1);

return [
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'access_ttl_minutes' => (int) env('JWT_ACCESS_TTL_MINUTES', 10),
        'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 14),
        'issuer' => env('APP_URL', 'http://localhost'),
    ],

    'cookies' => [
        'access_name' => env('AUTH_ACCESS_COOKIE', 'access_token'),
        'refresh_name' => env('AUTH_REFRESH_COOKIE', 'refresh_token'),
        'path' => '/',
        'domain' => env('AUTH_COOKIE_DOMAIN'),
        'secure' => env('AUTH_COOKIE_SECURE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],
];

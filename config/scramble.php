<?php

declare(strict_types=1);

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MD'
            **BioReport API** — privacy-first medical records platform.

            - **Authentication**: JWT via HTTP-only secure cookies (access + refresh).
            - **Account**: One account per user; create after registration.
            - **Diagnostic reports**: Attach observations (biomarkers) to reports.

            All authenticated endpoints require a valid JWT in the `Authorization: Bearer <token>` header or cookie.
            MD,
    ],

    'ui' => [
        'title' => 'BioReport API',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => null,
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];

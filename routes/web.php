<?php

declare(strict_types=1);

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): Response {
    return new Response(
        'Backend only. Deploy with Docker for the full application (SPA at /, API at /api).',
        200,
        ['Content-Type' => 'text/plain']
    );
});

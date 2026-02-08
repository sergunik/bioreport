<?php

declare(strict_types=1);

namespace App\System\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class HealthController extends Controller
{
    /**
     * Returns health check payload: service name, environment, version, and current timestamp (ISO 8601).
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'service' => config('app.name'),
            'environment' => config('app.env'),
            'version' => config('app.version'),
            'timestamp' => Carbon::now()->toIso8601String(),
        ], 200);
    }
}

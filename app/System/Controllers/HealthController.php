<?php

declare(strict_types=1);

namespace App\System\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class HealthController extends Controller
{
    /**
     * Returns health check payload: service name, environment, version, and current timestamp (ISO 8601).
     *
     * @unauthenticated
     */
    #[Response(200, 'Health status', examples: [['service' => 'BioReport', 'environment' => 'local', 'version' => '1.0.0', 'timestamp' => '2025-02-09T12:00:00+00:00']])]
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

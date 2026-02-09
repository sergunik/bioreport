<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\RefreshTokenAction;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefreshController extends Controller
{
    public function __construct(
        private readonly RefreshTokenAction $action,
    ) {}

    /**
     * @unauthenticated
     */
    #[Response(200, 'New tokens issued', examples: [['status' => 'ok']])]
    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(config('auth_tokens.cookies.refresh_name'));

        if ($refreshToken === null) {
            return response()->json(['message' => 'Refresh token required'], 401);
        }

        $response = response()->json();
        $success = $this->action->execute($refreshToken, $response);

        if (! $success) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        return $response->setData(['status' => 'ok']);
    }
}

<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\LogoutUserAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly LogoutUserAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(config('auth_tokens.cookies.refresh_name'));
        $response = response()->json(['status' => 'logged_out']);
        $this->action->execute($refreshToken, $response);

        return $response;
    }
}

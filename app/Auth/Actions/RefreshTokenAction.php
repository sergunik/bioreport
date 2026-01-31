<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use Symfony\Component\HttpFoundation\Response;

final readonly class RefreshTokenAction
{
    public function __construct(
        private AuthService $authService,
        private CookieService $cookieService,
    ) {}

    public function execute(string $refreshTokenValue, Response $response): bool
    {
        $user = $this->authService->refresh($refreshTokenValue);

        if ($user === null) {
            return false;
        }

        $tokens = $this->authService->issueTokenPair($user);
        $this->cookieService->setAuthCookies($response, $tokens['access'], $tokens['refresh']);

        return true;
    }
}

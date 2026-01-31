<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogoutUserAction
{
    public function __construct(
        private AuthService $authService,
        private CookieService $cookieService,
    ) {}

    public function execute(?string $refreshTokenValue, Response $response): void
    {
        $this->authService->logout($refreshTokenValue);
        $this->cookieService->clearAuthCookies($response);
    }
}

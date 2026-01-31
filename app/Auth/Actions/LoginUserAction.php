<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\DTOs\CredentialsDto;
use App\Auth\DTOs\UserDto;
use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use Symfony\Component\HttpFoundation\Response;

final readonly class LoginUserAction
{
    public function __construct(
        private AuthService $authService,
        private CookieService $cookieService,
    ) {}

    public function execute(CredentialsDto $credentials, Response $response): ?UserDto
    {
        $user = $this->authService->login($credentials);

        if ($user === null) {
            return null;
        }

        $tokens = $this->authService->issueTokenPair($user);
        $this->cookieService->setAuthCookies($response, $tokens['access'], $tokens['refresh']);

        return $this->authService->userToDto($user);
    }
}

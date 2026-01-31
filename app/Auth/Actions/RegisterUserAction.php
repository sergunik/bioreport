<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\DTOs\CredentialsDto;
use App\Auth\DTOs\UserDto;
use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterUserAction
{
    public function __construct(
        private AuthService $authService,
        private CookieService $cookieService,
    ) {}

    public function execute(CredentialsDto $credentials, Response $response): UserDto
    {
        $user = $this->authService->register($credentials);
        $tokens = $this->authService->issueTokenPair($user);
        $this->cookieService->setAuthCookies($response, $tokens['access'], $tokens['refresh']);

        return $this->authService->userToDto($user);
    }
}

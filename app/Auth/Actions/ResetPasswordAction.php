<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\DTOs\UserDto;
use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use App\Auth\Services\PasswordResetService;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResetPasswordAction
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private AuthService $authService,
        private CookieService $cookieService,
    ) {}

    public function execute(string $token, string $newPassword, Response $response): ?UserDto
    {
        $user = $this->passwordResetService->resetPassword($token, $newPassword);

        if ($user === null) {
            return null;
        }

        $tokens = $this->authService->issueTokenPair($user);
        $this->cookieService->setAuthCookies($response, $tokens['access'], $tokens['refresh']);

        return $this->authService->userToDto($user);
    }
}

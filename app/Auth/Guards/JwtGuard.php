<?php

declare(strict_types=1);

namespace App\Auth\Guards;

use App\Auth\Services\JwtService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

final class JwtGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        UserProvider $provider,
        private readonly Request $request,
        private readonly JwtService $jwtService,
        private readonly string $cookieName,
    ) {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->request->cookie($this->cookieName);

        if ($token === null) {
            return null;
        }

        $userId = $this->jwtService->validateAccessToken($token);

        if ($userId === null) {
            return null;
        }

        $this->user = $this->provider->retrieveById($userId);

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        return $this->user() !== null;
    }
}

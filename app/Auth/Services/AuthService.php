<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\DTOs\CredentialsDto;
use App\Auth\DTOs\UserDto;
use App\Models\RefreshToken;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;

final readonly class AuthService
{
    public function __construct(
        private JwtService $jwtService,
        private CookieService $cookieService,
        private int $refreshTtlDays,
    ) {}

    public function register(CredentialsDto $credentials): User
    {
        $user = User::query()->create([
            'email' => $credentials->email,
            'password' => Hash::make($credentials->password),
        ]);

        return $user;
    }

    public function login(CredentialsDto $credentials): ?User
    {
        $user = User::query()->where('email', $credentials->email)->first();

        if ($user === null || ! Hash::check($credentials->password, $user->password)) {
            return null;
        }

        $this->revokeRefreshTokensForUser($user);

        return $user;
    }

    public function issueTokenPair(User $user): array
    {
        $accessToken = $this->jwtService->createAccessToken($user);
        $refreshToken = $this->jwtService->createRefreshToken($user);
        $this->storeRefreshToken($user, $refreshToken);

        return ['access' => $accessToken, 'refresh' => $refreshToken];
    }

    public function refresh(string $refreshTokenValue): ?User
    {
        $userId = $this->jwtService->validateRefreshToken($refreshTokenValue);

        if ($userId === null) {
            return null;
        }

        $tokenHash = $this->hashRefreshToken($refreshTokenValue);
        $refreshToken = RefreshToken::query()
            ->where('user_id', $userId)
            ->where('token_hash', $tokenHash)
            ->first();

        if ($refreshToken === null || ! $refreshToken->isValid()) {
            return null;
        }

        $refreshToken->update(['revoked_at' => new DateTimeImmutable('now')]);
        $user = User::query()->find($userId);

        return $user;
    }

    public function logout(?string $refreshTokenValue): void
    {
        if ($refreshTokenValue === null) {
            return;
        }

        $userId = $this->jwtService->validateRefreshToken($refreshTokenValue);

        if ($userId === null) {
            return;
        }

        $tokenHash = $this->hashRefreshToken($refreshTokenValue);
        RefreshToken::query()
            ->where('user_id', $userId)
            ->where('token_hash', $tokenHash)
            ->update(['revoked_at' => new DateTimeImmutable('now')]);
    }

    public function revokeRefreshTokensForUser(User $user): void
    {
        RefreshToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => new DateTimeImmutable('now')]);
    }

    public function userToDto(User $user): UserDto
    {
        return new UserDto($user->id, $user->email);
    }

    private function storeRefreshToken(User $user, string $token): void
    {
        $expiresAt = (new DateTimeImmutable('now'))->modify("+{$this->refreshTtlDays} days");

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $this->hashRefreshToken($token),
            'expires_at' => $expiresAt,
        ]);
    }

    private function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

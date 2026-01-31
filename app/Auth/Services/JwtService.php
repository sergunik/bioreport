<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

final readonly class JwtService
{
    private const ALGORITHM = 'HS256';

    private const CLAIM_SUB = 'sub';

    private const CLAIM_TYPE_ACCESS = 'access';

    private const CLAIM_TYPE_REFRESH = 'refresh';

    public function __construct(
        private string $secret,
        private int $accessTtlMinutes,
        private int $refreshTtlDays,
        private string $issuer,
    ) {}

    public function createAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub' => $user->id,
            'type' => self::CLAIM_TYPE_ACCESS,
            'iat' => $now,
            'exp' => $now + ($this->accessTtlMinutes * 60),
            'iss' => $this->issuer,
        ];

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    public function createRefreshToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub' => $user->id,
            'type' => self::CLAIM_TYPE_REFRESH,
            'iat' => $now,
            'exp' => $now + ($this->refreshTtlDays * 86400),
            'iss' => $this->issuer,
        ];

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    public function validateAccessToken(string $token): ?string
    {
        $payload = $this->decodeAndValidate($token, self::CLAIM_TYPE_ACCESS);

        return $payload !== null ? $payload->sub : null;
    }

    public function validateRefreshToken(string $token): ?string
    {
        $payload = $this->decodeAndValidate($token, self::CLAIM_TYPE_REFRESH);

        return $payload !== null ? $payload->sub : null;
    }

    private function decodeAndValidate(string $token, string $expectedType): ?stdClass
    {
        try {
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (\Throwable) {
            return null;
        }

        if (! isset($payload->sub, $payload->type) || $payload->type !== $expectedType) {
            return null;
        }

        return $payload;
    }
}

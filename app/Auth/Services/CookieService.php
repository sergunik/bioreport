<?php

declare(strict_types=1);

namespace App\Auth\Services;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class CookieService
{
    public function __construct(
        private string $accessName,
        private string $refreshName,
        private string $path,
        private ?string $domain,
        private bool $secure,
        private bool $httpOnly,
        private string $sameSite,
        private int $accessTtlMinutes,
        private int $refreshTtlDays,
    ) {}

    public function setAuthCookies(SymfonyResponse $response, string $accessToken, string $refreshToken): void
    {
        $response->headers->setCookie(
            Cookie::create($this->accessName)
                ->withValue($accessToken)
                ->withPath($this->path)
                ->withDomain($this->domain ?? '')
                ->withSecure($this->secure)
                ->withHttpOnly($this->httpOnly)
                ->withSameSite($this->sameSite)
                ->withExpires(time() + ($this->accessTtlMinutes * 60))
        );
        $response->headers->setCookie(
            Cookie::create($this->refreshName)
                ->withValue($refreshToken)
                ->withPath($this->path)
                ->withDomain($this->domain ?? '')
                ->withSecure($this->secure)
                ->withHttpOnly($this->httpOnly)
                ->withSameSite($this->sameSite)
                ->withExpires(time() + ($this->refreshTtlDays * 86400))
        );
    }

    public function clearAuthCookies(SymfonyResponse $response): void
    {
        $response->headers->clearCookie(
            $this->accessName,
            $this->path,
            $this->domain
        );
        $response->headers->clearCookie(
            $this->refreshName,
            $this->path,
            $this->domain
        );
    }
}

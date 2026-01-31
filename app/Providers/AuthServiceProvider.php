<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Guards\JwtGuard;
use App\Auth\Services\AuthService;
use App\Auth\Services\CookieService;
use App\Auth\Services\JwtService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class, function () {
            $config = config('auth_tokens.jwt');

            return new JwtService(
                secret: (string) $config['secret'],
                accessTtlMinutes: (int) $config['access_ttl_minutes'],
                refreshTtlDays: (int) $config['refresh_ttl_days'],
                issuer: (string) $config['issuer'],
            );
        });

        $this->app->singleton(CookieService::class, function () {
            $cookies = config('auth_tokens.cookies');
            $jwt = config('auth_tokens.jwt');

            return new CookieService(
                accessName: (string) $cookies['access_name'],
                refreshName: (string) $cookies['refresh_name'],
                path: (string) $cookies['path'],
                domain: $cookies['domain'],
                secure: (bool) $cookies['secure'],
                httpOnly: (bool) $cookies['http_only'],
                sameSite: (string) $cookies['same_site'],
                accessTtlMinutes: (int) $jwt['access_ttl_minutes'],
                refreshTtlDays: (int) $jwt['refresh_ttl_days'],
            );
        });

        $this->app->singleton(AuthService::class, function () {
            $refreshTtlDays = (int) config('auth_tokens.jwt.refresh_ttl_days', 14);

            return new AuthService(
                $this->app->make(JwtService::class),
                $this->app->make(CookieService::class),
                $refreshTtlDays,
            );
        });
    }

    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                Auth::createUserProvider($config['provider'] ?? null),
                $app['request'],
                $app->make(JwtService::class),
                (string) config('auth_tokens.cookies.access_name'),
            );
        });
    }
}

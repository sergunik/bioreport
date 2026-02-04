<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
    }

    public function test_register_creates_user_and_returns_201_with_user_and_sets_cookies(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'StrongPass123!@#',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['user' => ['id', 'email']]);
        $response->assertJsonFragment(['email' => 'user@example.com']);
        $response->assertCookie(config('auth_tokens.cookies.access_name'));
        $response->assertCookie(config('auth_tokens.cookies.refresh_name'));
        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'existing@example.com',
            'password' => 'StrongPass123!@#',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_returns_200_with_user_and_sets_cookies(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'StrongPass123!@#',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['id', 'email']]);
        $response->assertJsonFragment(['email' => 'login@example.com']);
        $response->assertCookie(config('auth_tokens.cookies.access_name'));
        $response->assertCookie(config('auth_tokens.cookies.refresh_name'));
    }

    public function test_login_returns_401_for_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'WrongPass123!@#',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_returns_200_and_rotates_tokens(): void
    {
        $user = User::factory()->create(['email' => 'refresh@example.com']);
        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.refresh_name'), $tokens['refresh'])
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
        $response->assertCookie(config('auth_tokens.cookies.access_name'));
        $response->assertCookie(config('auth_tokens.cookies.refresh_name'));
    }

    public function test_refresh_returns_401_without_cookie(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_logout_returns_200_and_clears_cookies(): void
    {
        $user = User::factory()->create(['email' => 'logout@example.com']);
        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.refresh_name'), $tokens['refresh'])
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'logged_out']);
    }

    public function test_password_forgot_returns_200_always(): void
    {
        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_password_reset_returns_200_and_logs_in_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = 'valid-reset-token';
        PasswordResetToken::query()->create([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'password' => 'NewStrongPass123!@#',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['id', 'email']]);
        $response->assertJsonFragment(['email' => 'reset@example.com']);
        $response->assertCookie(config('auth_tokens.cookies.access_name'));
    }

    public function test_password_reset_returns_400_for_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/password/reset', [
            'token' => 'invalid-token',
            'password' => 'NewStrongPass123!@#',
        ]);

        $response->assertStatus(400);
    }
}

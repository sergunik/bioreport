<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class MeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
    }

    public function test_get_me_returns_profile_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'MeUser',
            'date_of_birth' => '1985-06-15',
            'sex' => 'female',
            'language' => 'uk',
            'timezone' => 'Europe/Kyiv',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'me@example.com',
            'nickname' => 'MeUser',
            'date_of_birth' => '1985-06-15',
            'sex' => 'female',
            'language' => 'uk',
            'timezone' => 'Europe/Kyiv',
        ]);
    }

    public function test_get_me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_patch_me_updates_profile_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'patch-me@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Before',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/me', [
                'nickname' => 'After',
                'language' => 'uk',
                'timezone' => 'Europe/Kyiv',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'updated']);

        $account = Account::query()->where('user_id', $user->id)->first();
        self::assertNotNull($account);
        self::assertSame('After', $account->nickname);
        self::assertSame('uk', $account->language);
        self::assertSame('Europe/Kyiv', $account->timezone);
    }

    public function test_patch_me_security_updates_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old-email@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/me/security', [
                'email' => 'new-email@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'updated']);

        $user->refresh();
        self::assertSame('new-email@example.com', $user->email);
    }

    public function test_patch_me_security_updates_password(): void
    {
        $user = User::factory()->create([
            'email' => 'pwd-change@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/me/security', [
                'current_password' => 'StrongPass123!@#',
                'password' => 'NewStrongPass456!@#',
                'password_confirmation' => 'NewStrongPass456!@#',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'updated']);

        $user->refresh();
        self::assertTrue(Hash::check('NewStrongPass456!@#', $user->password));
    }

    public function test_patch_me_security_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong-pwd@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/me/security', [
                'current_password' => 'WrongPassword',
                'password' => 'NewStrongPass456!@#',
                'password_confirmation' => 'NewStrongPass456!@#',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    }

    public function test_delete_me_requires_password_and_returns_204(): void
    {
        $user = User::factory()->create([
            'email' => 'delete-me@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'ToDelete',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->deleteJson('/api/me', [
                'password' => 'StrongPass123!@#',
            ]);

        $response->assertStatus(204);

        self::assertNull(User::query()->find($user->id));
        self::assertNull(Account::query()->where('user_id', $user->id)->first());
    }

    public function test_delete_me_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'no-delete@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->deleteJson('/api/me', [
                'password' => 'WrongPassword',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        self::assertNotNull(User::query()->find($user->id));
    }
}

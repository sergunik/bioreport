<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EncryptCookies::except(['access_token', 'refresh_token']);
    }

    public function test_create_account_creates_account_for_user_without_account(): void
    {
        $user = User::factory()->create([
            'email' => 'create-account@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->postJson('/api/account', [
                'sex' => 'male',
                'date_of_birth' => '1990-01-01',
                'nickname' => 'Adam',
                'language' => 'en',
                'timezone' => 'UTC',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'nickname' => 'Adam',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $account = Account::query()->where('user_id', $user->id)->first();
        self::assertNotNull($account);
    }

    public function test_get_current_account_returns_account_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'account@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Adam',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->getJson('/api/account');

        $response->assertStatus(200);
        $response->assertJson([
            'nickname' => 'Adam',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);
    }

    public function test_get_current_account_returns_500_when_missing_account(): void
    {
        $user = User::factory()->create([
            'email' => 'no-account@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->getJson('/api/account');

        $response->assertStatus(500);
    }

    public function test_update_account_updates_mutable_fields_only(): void
    {
        $user = User::factory()->create([
            'email' => 'update@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Old',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/account', [
                'nickname' => 'New',
                'language' => 'uk',
                'timezone' => 'Europe/Kyiv',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'updated']);

        $account = Account::query()->where('user_id', $user->id)->firstOrFail();
        self::assertSame('New', $account->nickname);
        self::assertSame('uk', $account->language);
        self::assertSame('Europe/Kyiv', $account->timezone);
        self::assertSame('1990-01-01', $account->date_of_birth->format('Y-m-d'));
        self::assertSame('male', $account->sex);
    }

    public function test_update_account_rejects_forbidden_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'forbidden@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'User',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->patchJson('/api/account', [
                'sex' => 'female',
                'date_of_birth' => '2000-01-01',
                'user_id' => 'other',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_account_returns_conflict_if_account_already_exists(): void
    {
        $user = User::factory()->create([
            'email' => 'existing-account@example.com',
            'password' => Hash::make('StrongPass123!@#'),
        ]);

        Account::query()->create([
            'user_id' => $user->id,
            'nickname' => 'Existing',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $tokens = $this->app->make(\App\Auth\Services\AuthService::class)->issueTokenPair($user);

        $response = $this->withCredentials()
            ->withUnencryptedCookie(config('auth_tokens.cookies.access_name'), $tokens['access'])
            ->postJson('/api/account', [
                'sex' => 'male',
                'date_of_birth' => '1990-01-01',
            ]);

        $response->assertStatus(409);
        $response->assertJson(['status' => 'account_exists']);
    }

    public function test_delete_account_deletes_user_and_clears_cookies(): void
    {
        $user = User::factory()->create([
            'email' => 'delete@example.com',
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
            ->withUnencryptedCookie(config('auth_tokens.cookies.refresh_name'), $tokens['refresh'])
            ->deleteJson('/api/account');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'account_deleted']);

        self::assertNull(User::query()->find($user->id));
        self::assertNull(Account::query()->where('user_id', $user->id)->first());
    }
}

<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class PasswordResetService
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function sendResetLink(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $token = Str::random(64);
        PasswordResetToken::query()->updateOrCreate(
            ['email' => $user->email],
            ['token' => hash('sha256', $token), 'created_at' => now()]
        );

        // TODO: send email with token (spec: "Send reset email")
        // For now we only ensure generic response; email sending can be wired via event/listener.
    }

    public function resetPassword(string $token, string $newPassword): ?User
    {
        $tokenHash = hash('sha256', $token);
        $record = PasswordResetToken::query()
            ->where('token', $tokenHash)
            ->first();

        if ($record === null) {
            return null;
        }

        $user = User::query()->where('email', $record->email)->first();

        if ($user === null) {
            return null;
        }

        $user->update(['password' => Hash::make($newPassword)]);
        $this->authService->revokeRefreshTokensForUser($user);
        PasswordResetToken::query()->where('email', $user->email)->delete();

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Me\Services;

use App\Auth\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class SecurityService
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function updateEmail(User $user, string $email): void
    {
        $user->update(['email' => $email]);
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is invalid');
        }

        $user->update(['password' => Hash::make($newPassword)]);
        $this->authService->revokeRefreshTokensForUser($user);
    }
}

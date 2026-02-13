<?php

declare(strict_types=1);

namespace App\Me\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class PrivacyService
{
    public function deleteUserWithPasswordConfirmation(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw new \InvalidArgumentException('Password is invalid');
        }

        $user->delete();
    }
}

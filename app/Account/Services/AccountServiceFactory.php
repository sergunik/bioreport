<?php

declare(strict_types=1);

namespace App\Account\Services;

use App\Models\User;

final readonly class AccountServiceFactory
{
    /**
     * Creates a user-scoped account service.
     */
    public function make(User $user): AccountService
    {
        return new AccountService($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Services\PasswordResetService;

final readonly class ForgotPasswordAction
{
    public function __construct(
        private PasswordResetService $passwordResetService,
    ) {}

    public function execute(string $email): void
    {
        $this->passwordResetService->sendResetLink($email);
    }
}

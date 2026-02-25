<?php

declare(strict_types=1);

namespace App\Account\Services;

use App\Account\DTOs\AccountCreateDto;
use App\Models\Account;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use LogicException;

final readonly class AccountService
{
    /**
     * Creates an account for the current user.
     */
    public function __construct(
        private User $user,
    ) {}

    /**
     * Creates an account for the current user.
     */
    public function create(AccountCreateDto $data): Account
    {
        $account = Account::query()->create([
            'user_id' => $this->user->id,
            'nickname' => $data->nickname,
            'date_of_birth' => $data->dateOfBirth,
            'sex' => $data->sex,
            'language' => $data->language,
            'timezone' => $data->timezone,
            'sensitive_words' => $data->sensitiveWords,
        ]);

        if ((string) $account->user_id !== (string) $this->user->id) {
            Log::error('Account created with mismatched user_id', [
                'expected_user_id' => $this->user->id,
                'actual_user_id' => $account->user_id,
            ]);

            throw new LogicException('Failed to create account for user');
        }

        return $account;
    }

    /**
     * Returns the account for the current user or throws.
     */
    public function getOrFail(): Account
    {
        $account = Account::query()->where('user_id', $this->user->id)->first();

        if ($account === null) {
            Log::error('Missing account for existing user', [
                'user_id' => $this->user->id,
            ]);

            throw new LogicException('Account not found for user');
        }

        return $account;
    }

    /**
     * Returns the account for the current user or null.
     */
    public function getOrNull(): ?Account
    {
        return Account::query()->where('user_id', $this->user->id)->first();
    }

    /**
     * Updates mutable fields for the current user account.
     */
    public function update(array $data): Account
    {
        $account = $this->getOrFail();

        $account->fill($data);
        $account->save();

        return $account;
    }

    /**
     * Deletes the current user account and all user data.
     */
    public function deleteAccountAndUser(): void
    {
        $account = $this->getOrFail();

        $account->delete();

        RefreshToken::query()
            ->where('user_id', $this->user->id)
            ->delete();

        $this->user->delete();
    }
}

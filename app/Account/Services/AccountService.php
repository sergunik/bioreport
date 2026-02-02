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
    public function createForUser(User $user, AccountCreateDto $data): Account
    {
        $account = Account::query()->create([
            'user_id' => $user->id,
            'nickname' => $data->nickname,
            'date_of_birth' => $data->dateOfBirth,
            'sex' => $data->sex,
            'language' => $data->language,
            'timezone' => $data->timezone,
        ]);

        if ((string) $account->user_id !== (string) $user->id) {
            Log::error('Account created with mismatched user_id', [
                'expected_user_id' => $user->id,
                'actual_user_id' => $account->user_id,
            ]);

            throw new LogicException('Failed to create account for user');
        }

        return $account;
    }

    public function getForUserOrFail(User $user): Account
    {
        $account = Account::query()->where('user_id', $user->id)->first();

        if ($account === null) {
            Log::error('Missing account for existing user', [
                'user_id' => $user->id,
            ]);

            throw new LogicException('Account not found for user');
        }

        return $account;
    }

    public function getForUserOrNull(User $user): ?Account
    {
        return Account::query()->where('user_id', $user->id)->first();
    }

    /**
     * @param  array{nickname?: string|null, language?: string, timezone?: string}  $data
     */
    public function updateForUser(User $user, array $data): Account
    {
        $account = $this->getForUserOrFail($user);

        $account->fill($data);
        $account->save();

        return $account;
    }

    public function deleteAccountAndUser(User $user): void
    {
        $account = $this->getForUserOrFail($user);

        $account->delete();

        RefreshToken::query()
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();
    }
}

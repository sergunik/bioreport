<?php

declare(strict_types=1);

namespace App\Me\DTOs;

use App\Account\DTOs\AccountDto;
use App\Models\Account;
use App\Models\User;

final readonly class MeDto
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $nickname,
        public string $dateOfBirth,
        public string $sex,
        public string $language,
        public string $timezone,
    ) {}

    public static function fromUserAndAccount(User $user, Account $account): self
    {
        $accountDto = AccountDto::fromModel($account);

        return new self(
            id: (string) $user->id,
            email: $user->email,
            nickname: $accountDto->nickname,
            dateOfBirth: $accountDto->dateOfBirth,
            sex: $accountDto->sex,
            language: $accountDto->language,
            timezone: $accountDto->timezone,
        );
    }

    /**
     * @return array{
     *     id: string,
     *     email: string,
     *     nickname: string|null,
     *     date_of_birth: string,
     *     sex: string,
     *     language: string,
     *     timezone: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'nickname' => $this->nickname,
            'date_of_birth' => $this->dateOfBirth,
            'sex' => $this->sex,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];
    }
}

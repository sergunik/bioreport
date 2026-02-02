<?php

declare(strict_types=1);

namespace App\Account\DTOs;

use App\Models\Account;

final readonly class AccountDto
{
    public function __construct(
        public string $id,
        public ?string $nickname,
        public string $dateOfBirth,
        public string $sex,
        public string $language,
        public string $timezone,
    ) {}

    public static function fromModel(Account $account): self
    {
        return new self(
            id: (string) $account->getKey(),
            nickname: $account->nickname,
            dateOfBirth: $account->date_of_birth->format('Y-m-d'),
            sex: $account->sex,
            language: $account->language,
            timezone: $account->timezone,
        );
    }

    /**
     * @return array{
     *     id: string,
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
            'nickname' => $this->nickname,
            'date_of_birth' => $this->dateOfBirth,
            'sex' => $this->sex,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];
    }
}

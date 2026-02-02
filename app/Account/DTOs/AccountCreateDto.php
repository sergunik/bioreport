<?php

declare(strict_types=1);

namespace App\Account\DTOs;

final readonly class AccountCreateDto
{
    public function __construct(
        public string $sex,
        public string $dateOfBirth,
        public ?string $nickname,
        public string $language,
        public string $timezone,
    ) {}

    /**
     * @param  array{
     *     sex: string,
     *     date_of_birth: string,
     *     nickname?: string|null,
     *     language?: string|null,
     *     timezone?: string|null
     * }  $data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            sex: $data['sex'],
            dateOfBirth: $data['date_of_birth'],
            nickname: $data['nickname'] ?? null,
            language: $data['language'] ?? 'en',
            timezone: $data['timezone'] ?? 'UTC',
        );
    }
}

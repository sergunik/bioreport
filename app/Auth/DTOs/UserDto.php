<?php

declare(strict_types=1);

namespace App\Auth\DTOs;

final readonly class UserDto
{
    public function __construct(
        public string $id,
        public string $email,
    ) {}

    /**
     * @return array{id: string, email: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}

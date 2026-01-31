<?php

declare(strict_types=1);

namespace App\Auth\DTOs;

final readonly class CredentialsDto
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * @param  array{email: string, password: string}  $data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }
}

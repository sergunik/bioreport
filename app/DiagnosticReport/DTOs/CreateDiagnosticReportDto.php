<?php

declare(strict_types=1);

namespace App\DiagnosticReport\DTOs;

final readonly class CreateDiagnosticReportDto
{
    public function __construct(
        public ?string $title,
        public ?string $notes,
    ) {}

    public static function fromValidated(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}

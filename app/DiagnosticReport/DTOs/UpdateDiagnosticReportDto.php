<?php

declare(strict_types=1);

namespace App\DiagnosticReport\DTOs;

final readonly class UpdateDiagnosticReportDto
{
    public function __construct(
        public ?string $notes,
    ) {}

    public static function fromValidated(array $data): self
    {
        return new self(
            notes: array_key_exists('notes', $data) ? $data['notes'] : null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\DiagnosticReport\DTOs;

final readonly class CreateDiagnosticReportDto
{
    public function __construct(
        public string $reportType,
        public ?string $notes,
        public array $observations,
    ) {}

    public static function fromValidated(array $data): self
    {
        $observations = [];
        foreach ($data['observations'] as $row) {
            $observations[] = ObservationItemDto::fromValidatedRow($row, null);
        }

        return new self(
            reportType: $data['report_type'],
            notes: $data['notes'] ?? null,
            observations: $observations,
        );
    }
}

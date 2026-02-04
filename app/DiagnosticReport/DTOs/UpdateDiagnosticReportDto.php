<?php

declare(strict_types=1);

namespace App\DiagnosticReport\DTOs;

final readonly class UpdateDiagnosticReportDto
{
    public function __construct(
        public ?string $reportType,
        public ?string $notes,
        public array $observations,
    ) {}

    public static function fromValidated(array $data): self
    {
        $observations = [];
        foreach ($data['observations'] ?? [] as $row) {
            $observations[] = ObservationItemDto::fromValidatedRow(
                $row,
                isset($row['id']) ? (int) $row['id'] : null,
            );
        }

        return new self(
            reportType: $data['report_type'] ?? null,
            notes: array_key_exists('notes', $data) ? $data['notes'] : null,
            observations: $observations,
        );
    }
}

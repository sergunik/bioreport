<?php

declare(strict_types=1);

namespace App\DiagnosticReport\DTOs;

final readonly class ObservationItemDto
{
    public function __construct(
        public ?int $id,
        public string $biomarkerName,
        public ?string $biomarkerCode,
        public string $originalValue,
        public string $originalUnit,
        public ?string $normalizedValue,
        public ?string $normalizedUnit,
        public ?string $referenceRangeMin,
        public ?string $referenceRangeMax,
        public ?string $referenceUnit,
    ) {}

    public static function fromValidatedRow(array $row, ?int $id): self
    {
        return new self(
            id: $id ?? (isset($row['id']) ? (int) $row['id'] : null),
            biomarkerName: (string) $row['biomarker_name'],
            biomarkerCode: isset($row['biomarker_code']) ? (string) $row['biomarker_code'] : null,
            originalValue: (string) $row['original_value'],
            originalUnit: (string) $row['original_unit'],
            normalizedValue: isset($row['normalized_value']) ? (string) $row['normalized_value'] : null,
            normalizedUnit: isset($row['normalized_unit']) ? (string) $row['normalized_unit'] : null,
            referenceRangeMin: isset($row['reference_range_min']) ? (string) $row['reference_range_min'] : null,
            referenceRangeMax: isset($row['reference_range_max']) ? (string) $row['reference_range_max'] : null,
            referenceUnit: isset($row['reference_unit']) ? (string) $row['reference_unit'] : null,
        );
    }
}

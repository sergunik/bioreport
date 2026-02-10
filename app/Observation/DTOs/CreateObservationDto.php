<?php

declare(strict_types=1);

namespace App\Observation\DTOs;

final readonly class CreateObservationDto
{
    public function __construct(
        public string $biomarkerName,
        public ?string $biomarkerCode,
        public string $value,
        public string $unit,
        public ?string $referenceRangeMin,
        public ?string $referenceRangeMax,
        public ?string $referenceUnit,
    ) {}

    public static function fromValidated(array $data): self
    {
        return new self(
            biomarkerName: (string) $data['biomarker_name'],
            biomarkerCode: isset($data['biomarker_code']) ? (string) $data['biomarker_code'] : null,
            value: (string) $data['value'],
            unit: (string) $data['unit'],
            referenceRangeMin: isset($data['reference_range_min']) ? (string) $data['reference_range_min'] : null,
            referenceRangeMax: isset($data['reference_range_max']) ? (string) $data['reference_range_max'] : null,
            referenceUnit: isset($data['reference_unit']) ? (string) $data['reference_unit'] : null,
        );
    }
}

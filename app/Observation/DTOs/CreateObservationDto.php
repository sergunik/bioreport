<?php

declare(strict_types=1);

namespace App\Observation\DTOs;

final readonly class CreateObservationDto
{
    public function __construct(
        public string $biomarkerName,
        public ?string $biomarkerCode,
        public string $valueType,
        public mixed $value,
        public ?string $unit,
        public ?float $referenceRangeMin,
        public ?float $referenceRangeMax,
        public ?string $referenceUnit,
    ) {}

    public static function fromValidated(array $data): self
    {
        return new self(
            biomarkerName: (string) $data['biomarker_name'],
            biomarkerCode: isset($data['biomarker_code']) ? (string) $data['biomarker_code'] : null,
            valueType: isset($data['value_type']) ? (string) $data['value_type'] : 'numeric',
            value: $data['value'],
            unit: isset($data['unit']) ? (string) $data['unit'] : null,
            referenceRangeMin: isset($data['reference_range_min']) ? (float) $data['reference_range_min'] : null,
            referenceRangeMax: isset($data['reference_range_max']) ? (float) $data['reference_range_max'] : null,
            referenceUnit: isset($data['reference_unit']) ? (string) $data['reference_unit'] : null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Observation\Value;

use App\Models\Observation;
use Illuminate\Validation\ValidationException;

final readonly class ObservationUpdatePayloadNormalizer
{
    public function __construct(
        private ObservationValuePayloadNormalizer $valuePayloadNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalize(array $validated, Observation $observation): array
    {
        $valueType = isset($validated['value_type'])
            ? (string) $validated['value_type']
            : (string) $observation->value_type;
        $valueTypeChanged = isset($validated['value_type']) && $valueType !== $observation->value_type;

        if (! array_key_exists('value', $validated) && $valueTypeChanged) {
            throw ValidationException::withMessages([
                'value' => ['The value field is required when value_type changes.'],
            ]);
        }

        if (! array_key_exists('value', $validated) && isset($validated['value_type'])) {
            $validated['value'] = $observation->value;
        }

        if (! array_key_exists('value', $validated)) {
            $this->assertReferenceFieldsAllowedWithoutValue($valueType, $validated);

            return $validated;
        }

        $payload = $this->buildNormalizationPayload($validated, $observation, $valueType);
        $typed = $this->valuePayloadNormalizer->normalize($payload);

        if ($valueType !== ObservationValueType::Numeric->value) {
            $validated['reference_range_min'] = null;
            $validated['reference_range_max'] = null;
            $validated['reference_unit'] = null;
        }

        return [
            ...$validated,
            ...$typed,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildNormalizationPayload(array $validated, Observation $observation, string $valueType): array
    {
        return [
            'value_type' => $valueType,
            'value' => $validated['value'],
            'unit' => array_key_exists('unit', $validated) ? $validated['unit'] : $observation->unit,
            'reference_range_min' => $valueType === ObservationValueType::Numeric->value
                ? (array_key_exists('reference_range_min', $validated)
                    ? $validated['reference_range_min']
                    : $observation->reference_range_min)
                : ($validated['reference_range_min'] ?? null),
            'reference_range_max' => $valueType === ObservationValueType::Numeric->value
                ? (array_key_exists('reference_range_max', $validated)
                    ? $validated['reference_range_max']
                    : $observation->reference_range_max)
                : ($validated['reference_range_max'] ?? null),
            'reference_unit' => $valueType === ObservationValueType::Numeric->value
                ? (array_key_exists('reference_unit', $validated)
                    ? $validated['reference_unit']
                    : $observation->reference_unit)
                : ($validated['reference_unit'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertReferenceFieldsAllowedWithoutValue(string $valueType, array $validated): void
    {
        if (
            $valueType !== ObservationValueType::Numeric->value
            && (
                (array_key_exists('reference_range_min', $validated) && $validated['reference_range_min'] !== null)
                || (array_key_exists('reference_range_max', $validated) && $validated['reference_range_max'] !== null)
                || (array_key_exists('reference_unit', $validated) && $validated['reference_unit'] !== null)
            )
        ) {
            throw ValidationException::withMessages([
                'reference_range_min' => ['Reference range fields are only allowed when value_type is numeric.'],
                'reference_range_max' => ['Reference range fields are only allowed when value_type is numeric.'],
                'reference_unit' => ['Reference range fields are only allowed when value_type is numeric.'],
            ]);
        }
    }
}

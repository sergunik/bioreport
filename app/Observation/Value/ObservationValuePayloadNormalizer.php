<?php

declare(strict_types=1);

namespace App\Observation\Value;

use Illuminate\Validation\ValidationException;

final class ObservationValuePayloadNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload): array
    {
        $errors = $this->collectValidationErrors($payload);
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $valueType = $this->resolveValueType($payload);
        $value = $payload['value'] ?? null;
        $unit = $this->nullableString($payload, 'unit');

        return match ($valueType) {
            ObservationValueType::Numeric => [
                'value_type' => ObservationValueType::Numeric->value,
                'value_number' => (float) $value,
                'value_boolean' => null,
                'value_text' => null,
                'unit' => $unit,
                'reference_range_min' => $this->nullableFloat($payload, 'reference_range_min'),
                'reference_range_max' => $this->nullableFloat($payload, 'reference_range_max'),
                'reference_unit' => $this->nullableString($payload, 'reference_unit'),
            ],
            ObservationValueType::Boolean => [
                'value_type' => ObservationValueType::Boolean->value,
                'value_number' => null,
                'value_boolean' => (bool) $value,
                'value_text' => null,
                'unit' => $unit,
                'reference_range_min' => null,
                'reference_range_max' => null,
                'reference_unit' => null,
            ],
            ObservationValueType::Text => [
                'value_type' => ObservationValueType::Text->value,
                'value_number' => null,
                'value_boolean' => null,
                'value_text' => (string) $value,
                'unit' => $unit,
                'reference_range_min' => null,
                'reference_range_max' => null,
                'reference_unit' => null,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array<int, string>>
     */
    public function collectValidationErrors(
        array $payload,
        bool $validateValue = true,
        bool $requireUnitForNumeric = true,
    ): array {
        $errors = [];
        $rawValueType = isset($payload['value_type']) ? (string) $payload['value_type'] : ObservationValueType::Numeric->value;

        if (! ObservationValueType::isValid($rawValueType)) {
            return [
                'value_type' => ['The selected value_type is invalid.'],
            ];
        }

        $valueType = ObservationValueType::from($rawValueType);
        $value = $payload['value'] ?? null;

        if ($valueType === ObservationValueType::Numeric) {
            if ($validateValue && ! is_numeric($value)) {
                $errors['value'][] = 'The value field must be a number when value_type is numeric.';
            }

            if ($requireUnitForNumeric) {
                $unit = $payload['unit'] ?? null;
                if ($unit === null || $unit === '') {
                    $errors['unit'][] = 'The unit field is required when value_type is numeric.';
                }
            }

            return $errors;
        }

        if ($validateValue && $valueType === ObservationValueType::Boolean && ! is_bool($value)) {
            $errors['value'][] = 'The value field must be a boolean when value_type is boolean.';
        }

        if ($validateValue && $valueType === ObservationValueType::Text && ! is_string($value)) {
            $errors['value'][] = 'The value field must be a string when value_type is text.';
        }

        if (array_key_exists('reference_range_min', $payload) && $payload['reference_range_min'] !== null) {
            $errors['reference_range_min'][] = 'Reference range fields are only allowed when value_type is numeric.';
        }

        if (array_key_exists('reference_range_max', $payload) && $payload['reference_range_max'] !== null) {
            $errors['reference_range_max'][] = 'Reference range fields are only allowed when value_type is numeric.';
        }

        if (array_key_exists('reference_unit', $payload) && $payload['reference_unit'] !== null) {
            $errors['reference_unit'][] = 'Reference range fields are only allowed when value_type is numeric.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveValueType(array $payload): ObservationValueType
    {
        $valueType = isset($payload['value_type']) ? (string) $payload['value_type'] : ObservationValueType::Numeric->value;

        return ObservationValueType::from($valueType);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        return isset($payload[$key]) ? (string) $payload[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableFloat(array $payload, string $key): ?float
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        return (float) $payload[$key];
    }
}

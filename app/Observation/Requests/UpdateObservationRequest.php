<?php

declare(strict_types=1);

namespace App\Observation\Requests;

use App\Observation\Value\ObservationValuePayloadNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'biomarker_name' => ['sometimes', 'string', 'max:255'],
            'biomarker_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'value_type' => ['sometimes', 'string', Rule::in(['numeric', 'boolean', 'text'])],
            'value' => ['sometimes'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'reference_range_min' => ['sometimes', 'nullable', 'numeric'],
            'reference_range_max' => ['sometimes', 'nullable', 'numeric'],
            'reference_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $this->all();
            if (! isset($data['value_type'])) {
                return;
            }

            $payload = [
                'value_type' => (string) $data['value_type'],
            ];
            foreach (['value', 'unit', 'reference_range_min', 'reference_range_max', 'reference_unit'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            $normalizer = new ObservationValuePayloadNormalizer;
            $errors = $normalizer->collectValidationErrors(
                $payload,
                validateValue: array_key_exists('value', $data),
                requireUnitForNumeric: false,
            );

            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }
}

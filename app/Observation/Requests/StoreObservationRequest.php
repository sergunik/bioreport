<?php

declare(strict_types=1);

namespace App\Observation\Requests;

use App\Observation\Value\ObservationValuePayloadNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'biomarker_name' => ['required', 'string', 'max:255'],
            'biomarker_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'value_type' => ['sometimes', 'string', Rule::in(['numeric', 'boolean', 'text'])],
            'value' => ['required'],
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
            $normalizer = new ObservationValuePayloadNormalizer;
            $errors = $normalizer->collectValidationErrors($data);

            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }
}

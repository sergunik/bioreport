<?php

declare(strict_types=1);

namespace App\DocumentExtraction\Requests;

use App\Observation\Value\ObservationValuePayloadNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreDocumentExtractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_uuid' => ['required', 'uuid'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'observations' => ['required', 'array', 'min:1'],
            'observations.*.biomarker_name' => ['required', 'string', 'max:255'],
            'observations.*.biomarker_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'observations.*.value_type' => ['sometimes', 'string', Rule::in(['numeric', 'boolean', 'text'])],
            'observations.*.value' => ['required'],
            'observations.*.unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'observations.*.reference_range_min' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_range_max' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $observations = $this->input('observations', []);
            if (! is_array($observations)) {
                return;
            }

            $normalizer = new ObservationValuePayloadNormalizer;

            foreach ($observations as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $errors = $normalizer->collectValidationErrors($item);
                foreach ($errors as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add("observations.{$index}.{$field}", $message);
                    }
                }
            }
        });
    }
}

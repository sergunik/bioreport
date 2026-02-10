<?php

declare(strict_types=1);

namespace App\Observation\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:64'],
            'reference_range_min' => ['sometimes', 'nullable', 'numeric'],
            'reference_range_max' => ['sometimes', 'nullable', 'numeric'],
            'reference_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}

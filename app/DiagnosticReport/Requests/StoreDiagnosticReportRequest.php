<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDiagnosticReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'observations' => ['required', 'array', 'min:1'],
            'observations.*.biomarker_name' => ['required', 'string', 'max:255'],
            'observations.*.biomarker_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'observations.*.original_value' => ['required', 'numeric'],
            'observations.*.original_unit' => ['required', 'string', 'max:64'],
            'observations.*.normalized_value' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.normalized_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'observations.*.reference_range_min' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_range_max' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}

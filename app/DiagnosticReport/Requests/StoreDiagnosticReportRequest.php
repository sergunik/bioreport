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
            /** @example "CBC" */
            'report_type' => ['required', 'string', 'max:255'],
            /** @example "Fasting sample" */
            'notes' => ['sometimes', 'nullable', 'string'],
            'observations' => ['required', 'array', 'min:1'],
            /** @example "Hemoglobin" */
            'observations.*.biomarker_name' => ['required', 'string', 'max:255'],
            /** @example "718-7" */
            'observations.*.biomarker_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            /** @example 14.2 */
            'observations.*.original_value' => ['required', 'numeric'],
            /** @example "g/dL" */
            'observations.*.original_unit' => ['required', 'string', 'max:64'],
            'observations.*.normalized_value' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.normalized_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'observations.*.reference_range_min' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_range_max' => ['sometimes', 'nullable', 'numeric'],
            'observations.*.reference_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}

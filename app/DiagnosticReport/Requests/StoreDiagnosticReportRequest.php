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
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

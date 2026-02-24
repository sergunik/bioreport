<?php

declare(strict_types=1);

namespace App\Account\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /** @example "male" */
            'sex' => ['required', 'string', 'in:male,female'],
            /** @example "1990-01-15" */
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            /** @example "John" */
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            /** @example "uk" */
            'language' => ['sometimes', 'string', 'size:2'],
            /** @example "Europe/Kyiv" */
            'timezone' => ['sometimes', 'string', 'timezone'],
            /** @example "ivan ivanov mx0000aa" */
            'sensitive_words' => ['sometimes', 'nullable', 'string', 'max:50000', 'regex:/^([a-zA-Z]+(\s+[a-zA-Z]+)*)?$/'],
        ];
    }
}

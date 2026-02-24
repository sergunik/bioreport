<?php

declare(strict_types=1);

namespace App\Account\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAccountRequest extends FormRequest
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
            /** @example "John" */
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            /** @example "uk" */
            'language' => ['sometimes', 'string', 'size:2'],
            /** @example "Europe/Kyiv" */
            'timezone' => ['sometimes', 'string', 'timezone'],
            /** @example "ivan ivanov mx0000aa" */
            'sensitive_words' => ['sometimes', 'nullable', 'string', 'max:50000', 'regex:/^([a-zA-Z]+(\s+[a-zA-Z]+)*)?$/'],
            'user_id' => ['prohibited'],
            'sex' => ['prohibited'],
            'date_of_birth' => ['prohibited'],
        ];
    }
}

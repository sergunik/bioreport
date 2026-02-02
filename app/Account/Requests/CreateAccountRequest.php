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
            'sex' => ['required', 'string', 'in:male,female'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'language' => ['sometimes', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ];
    }
}

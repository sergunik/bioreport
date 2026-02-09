<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            /** @example "user@example.com" */
            'email' => ['required', 'string', 'email'],
            /** @example "SecureP@ssw0rd123" */
            'password' => ['required', 'string'],
        ];
    }
}

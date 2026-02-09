<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            /** @example "SecureP@ssw0rd123" */
            'password' => ['required', 'string', 'min:12'],
        ];
    }
}

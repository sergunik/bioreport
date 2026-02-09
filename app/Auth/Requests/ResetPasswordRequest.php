<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
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
            /** @example "reset-token-from-email" */
            'token' => ['required', 'string'],
            /** @example "NewSecureP@ssw0rd123" */
            'password' => ['required', 'string', 'min:12'],
        ];
    }
}

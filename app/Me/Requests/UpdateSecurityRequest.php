<?php

declare(strict_types=1);

namespace App\Me\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdateSecurityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            /** @example "new-email@example.com" */
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
        ];

        if ($this->has('password') && (string) $this->input('password') !== '') {
            /** @example "CurrentSecureP@ss1" */
            $rules['current_password'] = ['required', 'current_password:jwt'];
            /** @example "NewSecureP@ssw0rd123" */
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        return $rules;
    }
}

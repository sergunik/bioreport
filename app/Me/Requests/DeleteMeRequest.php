<?php

declare(strict_types=1);

namespace App\Me\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Use only JSON body for validation so password is never read from query.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return $this->json()->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /** @example "CurrentSecureP@ss1" */
            'password' => ['required', 'current_password:jwt'],
        ];
    }
}

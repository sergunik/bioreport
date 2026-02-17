<?php

declare(strict_types=1);

namespace App\UploadedDocuments\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates PDF file upload for POST /documents.
 */
final class StoreDocumentRequest extends FormRequest
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
        $maxKb = config('uploaded_documents.max_size_kb', 10240);

        return [
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.$maxKb],
        ];
    }
}

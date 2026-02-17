<?php

declare(strict_types=1);

namespace Tests\Unit\UploadedDocuments;

use App\UploadedDocuments\Requests\StoreDocumentRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StoreDocumentRequestTest extends TestCase
{
    public function test_rules_require_file_and_pdf_mimetype(): void
    {
        $request = new StoreDocumentRequest;
        $rules = $request->rules();

        self::assertArrayHasKey('file', $rules);
        self::assertContains('required', $rules['file']);
        self::assertContains('file', $rules['file']);
        self::assertContains('mimetypes:application/pdf', $rules['file']);
    }

    public function test_validation_fails_for_non_pdf(): void
    {
        $file = UploadedFile::fake()->create('doc.txt', 100, 'text/plain');
        $validator = Validator::make(
            ['file' => $file],
            (new StoreDocumentRequest)->rules()
        );

        self::assertTrue($validator->fails());
    }

    public function test_validation_passes_for_pdf(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $validator = Validator::make(
            ['file' => $file],
            (new StoreDocumentRequest)->rules()
        );

        self::assertFalse($validator->fails());
    }

    public function test_validation_uses_configured_max_size_kb(): void
    {
        config(['uploaded_documents.max_size_kb' => 1]);
        $file = UploadedFile::fake()->create('doc.pdf', 2, 'application/pdf');
        $validator = Validator::make(
            ['file' => $file],
            (new StoreDocumentRequest)->rules()
        );

        self::assertTrue($validator->fails());
    }
}

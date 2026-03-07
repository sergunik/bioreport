<?php

declare(strict_types=1);

namespace Tests\Unit\DocumentExtraction;

use App\DocumentExtraction\DTOs\CreateDocumentExtractionDto;
use App\DocumentExtraction\Services\DocumentExtractionServiceFactory;
use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\UploadedDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DocumentExtractionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_creates_report_attachment_and_observations(): void
    {
        $user = User::factory()->create();
        $document = $this->createDocumentForUser($user, str_repeat('a', 64));
        $service = $this->app->make(DocumentExtractionServiceFactory::class)->make($user);

        $report = $service->extract(new CreateDocumentExtractionDto(
            documentUuid: $document->uuid,
            title: 'CBC Panel',
            notes: 'Fasting',
            observations: [
                [
                    'biomarker_name' => 'Hemoglobin',
                    'value_type' => 'numeric',
                    'value' => 14.2,
                    'unit' => 'g/dL',
                ],
                [
                    'biomarker_name' => 'COVID Antigen',
                    'value_type' => 'boolean',
                    'value' => false,
                ],
            ],
        ));

        self::assertNotNull($report->id);
        self::assertSame(2, Observation::withoutGlobalScope('user')->where('diagnostic_report_id', $report->id)->count());
        self::assertDatabaseHas('diagnostic_report_documents', [
            'diagnostic_report_id' => $report->id,
            'uploaded_document_uuid' => $document->uuid,
        ]);
        self::assertSame(1, DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->count());
        self::assertSame(2, Observation::withoutGlobalScope('user')->where('user_id', $user->id)->count());
    }

    public function test_extract_rolls_back_when_observation_payload_is_invalid(): void
    {
        $user = User::factory()->create();
        $document = $this->createDocumentForUser($user, str_repeat('b', 64));
        $service = $this->app->make(DocumentExtractionServiceFactory::class)->make($user);

        $this->expectException(ValidationException::class);

        try {
            $service->extract(new CreateDocumentExtractionDto(
                documentUuid: $document->uuid,
                title: 'Rollback case',
                notes: null,
                observations: [
                    [
                        'biomarker_name' => 'Hemoglobin',
                        'value_type' => 'numeric',
                        'value' => 14.2,
                        'unit' => 'g/dL',
                    ],
                    [
                        'biomarker_name' => 'COVID Antigen',
                        'value_type' => 'boolean',
                        'value' => true,
                        'reference_range_min' => 0.0,
                    ],
                ],
            ));
        } finally {
            self::assertSame(0, DiagnosticReport::withoutGlobalScope('user')->where('user_id', $user->id)->count());
            self::assertSame(0, Observation::withoutGlobalScope('user')->where('user_id', $user->id)->count());
            self::assertDatabaseCount('diagnostic_report_documents', 0);
        }
    }

    private function createDocumentForUser(User $user, string $sha256): UploadedDocument
    {
        $document = new UploadedDocument([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'storage_disk' => 'local',
            'file_size_bytes' => 120,
            'mime_type' => 'application/pdf',
            'file_hash_sha256' => $sha256,
        ]);
        $document->user_id = $user->id;
        $document->save();

        return $document;
    }
}

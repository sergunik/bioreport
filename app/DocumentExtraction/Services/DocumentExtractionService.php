<?php

declare(strict_types=1);

namespace App\DocumentExtraction\Services;

use App\DiagnosticReport\DTOs\CreateDiagnosticReportDto;
use App\DiagnosticReport\Services\DiagnosticReportService;
use App\DocumentExtraction\DTOs\CreateDocumentExtractionDto;
use App\DocumentExtraction\Exceptions\DocumentNotFoundException;
use App\Models\DiagnosticReport;
use App\Models\UploadedDocument;
use App\Observation\Services\ObservationService;
use App\UploadedDocuments\Services\UploadedDocumentService;
use Illuminate\Support\Facades\DB;

final readonly class DocumentExtractionService
{
    public function __construct(
        private UploadedDocumentService $uploadedDocumentService,
        private DiagnosticReportService $diagnosticReportService,
        private ObservationService $observationService,
    ) {}

    public function extract(CreateDocumentExtractionDto $dto): DiagnosticReport
    {
        $document = $this->uploadedDocumentService->getByUuid($dto->documentUuid);
        if ($document === null) {
            throw new DocumentNotFoundException('Document not found');
        }

        return DB::transaction(function () use ($dto, $document): DiagnosticReport {
            $reportDto = new CreateDiagnosticReportDto(
                title: $dto->title,
                notes: $dto->notes,
            );
            $report = $this->diagnosticReportService->create($reportDto);
            $this->attachDocument($report, $document);
            $this->observationService->createBatchForReport($report->id, $dto->observations);

            return $report->load(['observations', 'uploadedDocuments']);
        });
    }

    private function attachDocument(DiagnosticReport $report, UploadedDocument $document): void
    {
        $report->uploadedDocuments()->attach($document->uuid);
    }
}

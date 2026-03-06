<?php

declare(strict_types=1);

namespace App\DocumentExtraction\Services;

use App\DiagnosticReport\Services\DiagnosticReportServiceFactory;
use App\Models\User;
use App\Observation\Services\ObservationServiceFactory;
use App\UploadedDocuments\Services\UploadedDocumentServiceFactory;

final readonly class DocumentExtractionServiceFactory
{
    public function __construct(
        private UploadedDocumentServiceFactory $uploadedDocumentServiceFactory,
        private DiagnosticReportServiceFactory $diagnosticReportServiceFactory,
        private ObservationServiceFactory $observationServiceFactory,
    ) {}

    public function make(User $user): DocumentExtractionService
    {
        return new DocumentExtractionService(
            uploadedDocumentService: $this->uploadedDocumentServiceFactory->make($user),
            diagnosticReportService: $this->diagnosticReportServiceFactory->make($user),
            observationService: $this->observationServiceFactory->make($user),
        );
    }
}

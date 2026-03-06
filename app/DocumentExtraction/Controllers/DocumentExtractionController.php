<?php

declare(strict_types=1);

namespace App\DocumentExtraction\Controllers;

use App\DiagnosticReport\Resources\DiagnosticReportResource;
use App\DocumentExtraction\DTOs\CreateDocumentExtractionDto;
use App\DocumentExtraction\Exceptions\DocumentNotFoundException;
use App\DocumentExtraction\Requests\StoreDocumentExtractionRequest;
use App\DocumentExtraction\Services\DocumentExtractionService;
use App\DocumentExtraction\Services\DocumentExtractionServiceFactory;
use App\Http\Controllers\AuthenticatedController;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DocumentExtractionController extends AuthenticatedController
{
    private readonly DocumentExtractionService $documentExtractionService;

    public function __construct(
        DocumentExtractionServiceFactory $documentExtractionServiceFactory,
    ) {
        parent::__construct();
        $this->documentExtractionService = $documentExtractionServiceFactory->make($this->user);
    }

    #[ScrambleResponse(201, 'Created report from extracted document', examples: [['id' => 1, 'title' => 'CBC Panel', 'notes' => 'Fasting sample', 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z', 'observations' => [['id' => 1, 'biomarker_name' => 'Hemoglobin', 'biomarker_code' => '718-7', 'value_type' => 'numeric', 'value' => 14.2, 'unit' => 'g/dL', 'reference_range_min' => null, 'reference_range_max' => null, 'reference_unit' => null, 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z']], 'document_uuids' => ['9d3f8a2b-1c4e-4f5a-b6d7-8e9f0a1b2c3d']]])]
    public function store(StoreDocumentExtractionRequest $request): JsonResponse
    {
        try {
            $dto = CreateDocumentExtractionDto::fromValidated($request->validated());
            $report = $this->documentExtractionService->extract($dto);
        } catch (DocumentNotFoundException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            (new DiagnosticReportResource($report))->toArray($request),
            Response::HTTP_CREATED,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Controllers;

use App\DiagnosticReport\DTOs\CreateDiagnosticReportDto;
use App\DiagnosticReport\Requests\StoreDiagnosticReportRequest;
use App\DiagnosticReport\Requests\UpdateDiagnosticReportRequest;
use App\DiagnosticReport\Resources\DiagnosticReportResource;
use App\DiagnosticReport\Services\DiagnosticReportService;
use App\DiagnosticReport\Services\DiagnosticReportServiceFactory;
use App\Http\Controllers\AuthenticatedController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Diagnostic reports and observations CRUD.
 *
 * OpenAPI examples:
 *   POST /api/diagnostic-reports
 *   Request: { "report_type": "CBC", "notes": "Fasting sample", "observations": [{ "biomarker_name": "Hemoglobin", "biomarker_code": "718-7", "original_value": 14.2, "original_unit": "g/dL", "normalized_value": 14.2, "normalized_unit": "g/dL", "reference_range_min": 12, "reference_range_max": 16, "reference_unit": "g/dL" }] }
 *   Response: 201 with DiagnosticReportResource
 *
 *   PATCH /api/diagnostic-reports/{id}
 *   Request: { "observations": [{ "id": 42, "biomarker_name": "Hemoglobin", "original_value": 13.8 }, { "biomarker_name": "Hematocrit", "biomarker_code": "4544-3", "original_value": 42.1, "original_unit": "%" }] }
 *   Response: 200 with DiagnosticReportResource
 */
final class DiagnosticReportController extends AuthenticatedController
{
    private readonly DiagnosticReportService $diagnosticReportService;

    /**
     * Initializes the controller with the current user.
     */
    public function __construct(
        DiagnosticReportServiceFactory $diagnosticReportServiceFactory,
    ) {
        parent::__construct();

        $this->diagnosticReportService = $diagnosticReportServiceFactory->make($this->user);
    }

    /**
     * Creates a diagnostic report for the current user.
     */
    public function store(StoreDiagnosticReportRequest $request): JsonResponse
    {
        $dto = CreateDiagnosticReportDto::fromValidated($request->validated());
        $report = $this->diagnosticReportService->create($dto);

        return response()->json(
            (new DiagnosticReportResource($report))->toArray($request),
            Response::HTTP_CREATED,
        );
    }

    /**
     * Lists diagnostic reports for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = $this->diagnosticReportService->list();

        return response()->json([
            'data' => DiagnosticReportResource::collection($reports)->toArray($request),
        ]);
    }

    /**
     * Returns a single diagnostic report for the current user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $report = $this->diagnosticReportService->getById($id);
        if ($report === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json((new DiagnosticReportResource($report))->toArray($request));
    }

    /**
     * Updates a diagnostic report for the current user.
     */
    public function update(UpdateDiagnosticReportRequest $request, int $id): JsonResponse
    {
        try {
            $report = $this->diagnosticReportService->update($id, $request->validated());
        } catch (InvalidArgumentException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json((new DiagnosticReportResource($report))->toArray($request));
    }

    /**
     * Deletes a diagnostic report for the current user.
     */
    public function destroy(int $id): JsonResponse
    {
        $report = $this->diagnosticReportService->getById($id);
        if ($report === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->diagnosticReportService->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

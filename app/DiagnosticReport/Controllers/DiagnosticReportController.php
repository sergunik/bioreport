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
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

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
    #[ScrambleResponse(201, 'Created report', examples: [['id' => 1, 'notes' => 'Fasting sample', 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z', 'observations' => []]])]
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
    #[ScrambleResponse(200, 'List of reports', examples: [['data' => [['id' => 1, 'notes' => null, 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z', 'observations' => []]]]])]
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
    #[ScrambleResponse(200, 'Single report', examples: [['id' => 1, 'notes' => null, 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z', 'observations' => [['id' => 1, 'biomarker_name' => 'Hemoglobin', 'biomarker_code' => '718-7', 'value' => 14.2, 'unit' => 'g/dL', 'reference_range_min' => null, 'reference_range_max' => null, 'reference_unit' => null, 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z']]]])]
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
    #[ScrambleResponse(200, 'Updated report', examples: [['id' => 1, 'notes' => 'Updated', 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:30:00.000000Z', 'observations' => []]])]
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

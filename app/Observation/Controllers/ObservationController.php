<?php

declare(strict_types=1);

namespace App\Observation\Controllers;

use App\DiagnosticReport\Resources\ObservationResource;
use App\Http\Controllers\AuthenticatedController;
use App\Observation\DTOs\CreateObservationDto;
use App\Observation\Requests\StoreObservationRequest;
use App\Observation\Requests\UpdateObservationRequest;
use App\Observation\Services\ObservationService;
use App\Observation\Services\ObservationServiceFactory;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class ObservationController extends AuthenticatedController
{
    private readonly ObservationService $observationService;

    public function __construct(
        ObservationServiceFactory $observationServiceFactory,
    ) {
        parent::__construct();

        $this->observationService = $observationServiceFactory->make($this->user);
    }

    /**
     * Creates an observation for a diagnostic report owned by the current user.
     */
    #[ScrambleResponse(201, 'Created observation', examples: [['id' => 1, 'biomarker_name' => 'Hemoglobin', 'biomarker_code' => '718-7', 'value' => 14.2, 'unit' => 'g/dL', 'reference_range_min' => 12.0, 'reference_range_max' => 16.0, 'reference_unit' => 'g/dL', 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z']])]
    public function store(StoreObservationRequest $request, int $diagnosticReportId): JsonResponse
    {
        try {
            $dto = CreateObservationDto::fromValidated($request->validated());
            $observation = $this->observationService->createForReport($diagnosticReportId, $dto);
        } catch (InvalidArgumentException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            (new ObservationResource($observation))->toArray($request),
            Response::HTTP_CREATED,
        );
    }

    /**
     * Returns a single observation for the current user.
     */
    #[ScrambleResponse(200, 'Single observation', examples: [['id' => 1, 'biomarker_name' => 'Hemoglobin', 'biomarker_code' => '718-7', 'value' => 14.2, 'unit' => 'g/dL', 'reference_range_min' => null, 'reference_range_max' => null, 'reference_unit' => null, 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:00:00.000000Z']])]
    public function show(Request $request, int $id): JsonResponse
    {
        $observation = $this->observationService->getById($id);
        if ($observation === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json((new ObservationResource($observation))->toArray($request));
    }

    /**
     * Updates an observation for the current user.
     */
    #[ScrambleResponse(200, 'Updated observation', examples: [['id' => 1, 'biomarker_name' => 'Hemoglobin', 'biomarker_code' => '718-7', 'value' => 13.8, 'unit' => 'g/dL', 'reference_range_min' => 12.0, 'reference_range_max' => 16.0, 'reference_unit' => 'g/dL', 'created_at' => '2025-02-09T12:00:00.000000Z', 'updated_at' => '2025-02-09T12:30:00.000000Z']])]
    public function update(UpdateObservationRequest $request, int $id): JsonResponse
    {
        try {
            $observation = $this->observationService->update($id, $request->validated());
        } catch (InvalidArgumentException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json((new ObservationResource($observation))->toArray($request));
    }

    /**
     * Deletes an observation for the current user.
     */
    #[ScrambleResponse(204, 'Observation deleted')]
    public function destroy(int $id): JsonResponse
    {
        $observation = $this->observationService->getById($id);
        if ($observation === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->observationService->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

declare(strict_types=1);

namespace App\Observation\Services;

use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\User;
use App\Observation\DTOs\CreateObservationDto;
use App\Observation\Value\ObservationUpdatePayloadNormalizer;
use App\Observation\Value\ObservationValuePayloadNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class ObservationService
{
    public function __construct(
        private User $user,
        private ObservationValuePayloadNormalizer $valuePayloadNormalizer,
        private ObservationUpdatePayloadNormalizer $updatePayloadNormalizer,
    ) {}

    public function createForReport(int $diagnosticReportId, CreateObservationDto $dto): Observation
    {
        $report = $this->assertReportOwnership($diagnosticReportId);
        $typedPayload = $this->valuePayloadNormalizer->normalize([
            'value_type' => $dto->valueType,
            'value' => $dto->value,
            'unit' => $dto->unit,
            'reference_range_min' => $dto->referenceRangeMin,
            'reference_range_max' => $dto->referenceRangeMax,
            'reference_unit' => $dto->referenceUnit,
        ]);

        return $this->createObservation($report, [
            'user_id' => $this->user->id,
            'biomarker_name' => $dto->biomarkerName,
            'biomarker_code' => $dto->biomarkerCode,
            ...$typedPayload,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $observations
     * @return array<int, Observation>
     */
    public function createBatchForReport(int $diagnosticReportId, array $observations): array
    {
        $report = $this->assertReportOwnership($diagnosticReportId);

        /** @var array<int, Observation> $created */
        $created = DB::transaction(function () use ($observations, $report): array {
            $items = [];
            foreach ($observations as $index => $payload) {
                try {
                    $typedPayload = $this->valuePayloadNormalizer->normalize($payload);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(
                        $this->prefixValidationErrors($exception->errors(), "observations.{$index}."),
                    );
                }

                $items[] = $this->createObservation($report, [
                    'user_id' => $this->user->id,
                    'biomarker_name' => (string) ($payload['biomarker_name'] ?? ''),
                    'biomarker_code' => isset($payload['biomarker_code']) ? (string) $payload['biomarker_code'] : null,
                    ...$typedPayload,
                ]);
            }

            return $items;
        });

        return $created;
    }

    /**
     * @return Collection<int, Observation>
     */
    public function list(): Collection
    {
        return Observation::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getById(int $id): ?Observation
    {
        return Observation::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->whereKey($id)
            ->first();
    }

    public function update(int $id, array $validated): Observation
    {
        $observation = $this->getByIdOrFail($id);
        $normalized = $this->updatePayloadNormalizer->normalize($validated, $observation);
        $observation->fill($normalized);

        if ($observation->isDirty()) {
            $observation->save();
        }

        return $observation;
    }

    public function delete(int $id): void
    {
        $this->getByIdOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createObservation(DiagnosticReport $report, array $attributes): Observation
    {
        return Observation::withoutGlobalScope('user')->create([
            ...$attributes,
            'diagnostic_report_id' => $report->id,
        ]);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @return array<string, array<int, string>>
     */
    private function prefixValidationErrors(array $errors, string $prefix): array
    {
        $prefixedErrors = [];
        foreach ($errors as $field => $messages) {
            $prefixedErrors[$prefix.$field] = $messages;
        }

        return $prefixedErrors;
    }

    private function getByIdOrFail(int $id): Observation
    {
        $observation = $this->getById($id);
        if ($observation === null) {
            throw new InvalidArgumentException('Observation not found');
        }

        return $observation;
    }

    private function assertReportOwnership(int $diagnosticReportId): DiagnosticReport
    {
        $report = DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->whereKey($diagnosticReportId)
            ->first();

        if ($report === null) {
            throw new InvalidArgumentException('Diagnostic report not found');
        }

        return $report;
    }
}

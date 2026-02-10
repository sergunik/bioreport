<?php

declare(strict_types=1);

namespace App\Observation\Services;

use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\User;
use App\Observation\DTOs\CreateObservationDto;
use InvalidArgumentException;

final readonly class ObservationService
{
    public function __construct(
        private User $user,
    ) {}

    public function createForReport(int $diagnosticReportId, CreateObservationDto $dto): Observation
    {
        $report = DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->whereKey($diagnosticReportId)
            ->first();

        if ($report === null) {
            throw new InvalidArgumentException('Diagnostic report not found');
        }

        return Observation::withoutGlobalScope('user')->create([
            'user_id' => $this->user->id,
            'diagnostic_report_id' => $report->id,
            'biomarker_name' => $dto->biomarkerName,
            'biomarker_code' => $dto->biomarkerCode,
            'value' => $dto->value,
            'unit' => $dto->unit,
            'reference_range_min' => $dto->referenceRangeMin,
            'reference_range_max' => $dto->referenceRangeMax,
            'reference_unit' => $dto->referenceUnit,
        ]);
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
        $observation = $this->getById($id);
        if ($observation === null) {
            throw new InvalidArgumentException('Observation not found');
        }

        if (array_key_exists('biomarker_name', $validated)) {
            $observation->biomarker_name = $validated['biomarker_name'];
        }
        if (array_key_exists('biomarker_code', $validated)) {
            $observation->biomarker_code = $validated['biomarker_code'];
        }
        if (array_key_exists('value', $validated)) {
            $observation->value = $validated['value'];
        }
        if (array_key_exists('unit', $validated)) {
            $observation->unit = $validated['unit'];
        }
        if (array_key_exists('reference_range_min', $validated)) {
            $observation->reference_range_min = $validated['reference_range_min'];
        }
        if (array_key_exists('reference_range_max', $validated)) {
            $observation->reference_range_max = $validated['reference_range_max'];
        }
        if (array_key_exists('reference_unit', $validated)) {
            $observation->reference_unit = $validated['reference_unit'];
        }

        if ($observation->isDirty()) {
            $observation->save();
        }

        return $observation;
    }

    public function delete(int $id): void
    {
        $observation = $this->getById($id);
        if ($observation !== null) {
            $observation->delete();
        }
    }
}

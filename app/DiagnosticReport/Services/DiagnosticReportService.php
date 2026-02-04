<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Services;

use App\DiagnosticReport\DTOs\CreateDiagnosticReportDto;
use App\DiagnosticReport\DTOs\ObservationItemDto;
use App\DiagnosticReport\Enums\ReportSource;
use App\Models\DiagnosticReport;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

final readonly class DiagnosticReportService
{
    /**
     * Creates a report service scoped to the current user.
     */
    public function __construct(
        private User $user,
    ) {}

    /**
     * Creates a diagnostic report for the current user.
     */
    public function create(CreateDiagnosticReportDto $dto): DiagnosticReport
    {
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $this->user->id,
            'report_type' => $dto->reportType,
            'source' => ReportSource::Manual,
            'notes' => $dto->notes,
        ]);

        foreach ($dto->observations as $obs) {
            $this->createObservationForReport($report, $obs);
        }

        return $report->load('observations');
    }

    /**
     * Returns diagnostic reports for the current user.
     */
    public function list(): Collection
    {
        return DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->with('observations')
            ->get();
    }

    /**
     * Returns a diagnostic report for the current user by id.
     */
    public function getById(int $id): ?DiagnosticReport
    {
        return DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->with('observations')
            ->whereKey($id)
            ->first();
    }

    /**
     * Updates a diagnostic report for the current user.
     */
    public function update(int $id, array $validated): DiagnosticReport
    {
        $report = $this->getById($id);
        if ($report === null) {
            throw new InvalidArgumentException('Diagnostic report not found');
        }

        if (array_key_exists('report_type', $validated)) {
            $report->report_type = $validated['report_type'];
        }
        if (array_key_exists('notes', $validated)) {
            $report->notes = $validated['notes'];
        }
        if ($report->isDirty()) {
            $report->save();
        }

        if (! array_key_exists('observations', $validated)) {
            return $report->load('observations');
        }

        $rows = $validated['observations'] ?? [];
        $existing = $report->observations()->get()->keyBy('id');

        $keepIds = [];
        foreach ($rows as $row) {
            $obs = ObservationItemDto::fromValidatedRow(
                $row,
                isset($row['id']) ? (int) $row['id'] : null,
            );
            if ($obs->id !== null) {
                $current = $existing->get($obs->id);
                if (! $current instanceof Observation) {
                    throw new InvalidArgumentException('Observation not found for report');
                }
                $this->fillObservationFromDto($current, $obs);
                $current->save();
                $keepIds[] = $current->id;

                continue;
            }

            $newObs = $this->createObservationForReport($report, $obs);
            $keepIds[] = $newObs->id;
        }

        $report->observations()->whereNotIn('id', $keepIds)->delete();

        return $report->load('observations');
    }

    /**
     * Deletes a diagnostic report for the current user.
     */
    public function delete(int $id): void
    {
        $report = $this->getById($id);
        if ($report !== null) {
            $report->delete();
        }
    }

    private function createObservationForReport(DiagnosticReport $report, ObservationItemDto $dto): Observation
    {
        return $report->observations()->create([
            'biomarker_name' => $dto->biomarkerName,
            'biomarker_code' => $dto->biomarkerCode,
            'original_value' => $dto->originalValue,
            'original_unit' => $dto->originalUnit,
            'normalized_value' => $dto->normalizedValue,
            'normalized_unit' => $dto->normalizedUnit,
            'reference_range_min' => $dto->referenceRangeMin,
            'reference_range_max' => $dto->referenceRangeMax,
            'reference_unit' => $dto->referenceUnit,
        ]);
    }

    private function fillObservationFromDto(Observation $observation, ObservationItemDto $dto): void
    {
        $observation->biomarker_name = $dto->biomarkerName;
        $observation->biomarker_code = $dto->biomarkerCode;
        $observation->original_value = $dto->originalValue;
        $observation->original_unit = $dto->originalUnit;
        $observation->normalized_value = $dto->normalizedValue;
        $observation->normalized_unit = $dto->normalizedUnit;
        $observation->reference_range_min = $dto->referenceRangeMin;
        $observation->reference_range_max = $dto->referenceRangeMax;
        $observation->reference_unit = $dto->referenceUnit;
    }
}

<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Services;

use App\DiagnosticReport\DTOs\CreateDiagnosticReportDto;
use App\Models\DiagnosticReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

final readonly class DiagnosticReportService
{
    public function __construct(
        private User $user,
    ) {}

    public function create(CreateDiagnosticReportDto $dto): DiagnosticReport
    {
        $report = DiagnosticReport::withoutGlobalScope('user')->create([
            'user_id' => $this->user->id,
            'notes' => $dto->notes,
        ]);

        return $report->load('observations');
    }

    public function list(): Collection
    {
        return DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->with('observations')
            ->get();
    }

    public function getById(int $id): ?DiagnosticReport
    {
        return DiagnosticReport::withoutGlobalScope('user')
            ->where('user_id', $this->user->id)
            ->with('observations')
            ->whereKey($id)
            ->first();
    }

    public function update(int $id, array $validated): DiagnosticReport
    {
        $report = $this->getById($id);
        if ($report === null) {
            throw new InvalidArgumentException('Diagnostic report not found');
        }

        if (array_key_exists('notes', $validated)) {
            $report->notes = $validated['notes'];
        }
        if ($report->isDirty()) {
            $report->save();
        }

        return $report->load('observations');
    }

    public function delete(int $id): void
    {
        $report = $this->getById($id);
        if ($report !== null) {
            $report->delete();
        }
    }
}

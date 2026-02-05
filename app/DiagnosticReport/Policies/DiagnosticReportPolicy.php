<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Policies;

use App\Models\DiagnosticReport;
use App\Models\User;

final class DiagnosticReportPolicy
{
    public function view(User $user, DiagnosticReport $report): bool
    {
        return (int) $report->user_id === (int) $user->id;
    }

    public function update(User $user, DiagnosticReport $report): bool
    {
        return (int) $report->user_id === (int) $user->id;
    }

    public function delete(User $user, DiagnosticReport $report): bool
    {
        return (int) $report->user_id === (int) $user->id;
    }
}

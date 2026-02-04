<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Services;

use App\Models\User;

final readonly class DiagnosticReportServiceFactory
{
    /**
     * Creates a user-scoped diagnostic report service.
     */
    public function make(User $user): DiagnosticReportService
    {
        return new DiagnosticReportService($user);
    }
}

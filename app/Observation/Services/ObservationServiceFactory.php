<?php

declare(strict_types=1);

namespace App\Observation\Services;

use App\Models\User;

final readonly class ObservationServiceFactory
{
    public function make(User $user): ObservationService
    {
        return new ObservationService($user);
    }
}

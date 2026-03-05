<?php

declare(strict_types=1);

namespace App\Observation\Services;

use App\Models\User;
use App\Observation\Value\ObservationUpdatePayloadNormalizer;
use App\Observation\Value\ObservationValuePayloadNormalizer;

final readonly class ObservationServiceFactory
{
    public function make(User $user): ObservationService
    {
        $valuePayloadNormalizer = new ObservationValuePayloadNormalizer;

        return new ObservationService(
            $user,
            $valuePayloadNormalizer,
            new ObservationUpdatePayloadNormalizer($valuePayloadNormalizer),
        );
    }
}

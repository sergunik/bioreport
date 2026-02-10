<?php

declare(strict_types=1);

namespace App\Observation\Policies;

use App\Models\Observation;
use App\Models\User;

final class ObservationPolicy
{
    public function view(User $user, Observation $observation): bool
    {
        return (int) $observation->user_id === (int) $user->id;
    }

    public function update(User $user, Observation $observation): bool
    {
        return (int) $observation->user_id === (int) $user->id;
    }

    public function delete(User $user, Observation $observation): bool
    {
        return (int) $observation->user_id === (int) $user->id;
    }
}

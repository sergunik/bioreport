<?php

declare(strict_types=1);

namespace App\Observation\Value;

enum ObservationValueType: string
{
    case Numeric = 'numeric';
    case Boolean = 'boolean';
    case Text = 'text';

    public static function isValid(string $valueType): bool
    {
        return self::tryFrom($valueType) !== null;
    }
}

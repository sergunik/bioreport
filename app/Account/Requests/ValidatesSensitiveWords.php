<?php

declare(strict_types=1);

namespace App\Account\Requests;

trait ValidatesSensitiveWords
{
    public const SENSITIVE_WORDS_REGEX = '/^([a-zA-Z\p{Cyrillic}0-9\']+(\s+[a-zA-Z\p{Cyrillic}0-9\']+)*)?$/u';
}

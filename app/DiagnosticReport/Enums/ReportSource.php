<?php

declare(strict_types=1);

namespace App\DiagnosticReport\Enums;

enum ReportSource: string
{
    case Manual = 'manual';
    case Pdf = 'pdf';
    case Integration = 'integration';
}

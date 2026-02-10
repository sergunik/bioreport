<?php

declare(strict_types=1);

namespace Tests\Unit\DiagnosticReport;

use App\Models\Observation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ObservationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_reference_range_returns_true_when_min_set(): void
    {
        $obs = new Observation([
            'reference_range_min' => 10.0,
            'reference_range_max' => null,
        ]);
        $obs->id = 1;
        self::assertTrue($obs->hasReferenceRange());
    }

    public function test_has_reference_range_returns_true_when_max_set(): void
    {
        $obs = new Observation([
            'reference_range_min' => null,
            'reference_range_max' => 20.0,
        ]);
        $obs->id = 1;
        self::assertTrue($obs->hasReferenceRange());
    }

    public function test_has_reference_range_returns_false_when_both_null(): void
    {
        $obs = new Observation([
            'reference_range_min' => null,
            'reference_range_max' => null,
        ]);
        $obs->id = 1;
        self::assertFalse($obs->hasReferenceRange());
    }
}
